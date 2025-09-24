<?php

namespace App\Services;

use App\Models\Motivation;
use App\Models\Rate;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public static function translateRoleName($role): string
    {
        $roleName = '---';
        match ($role) {
            'admin' => $roleName = 'Руководитель',
            'storekeeper' => $roleName = 'Кладовщик',
            'seamstress' => $roleName = 'Швея',
            'cutter' => $roleName = 'Закройщик',
            default => $roleName,
        };

        return $roleName;
    }

    public static function getListSeamstressesWorkingToday(): Collection
    {
        return self::getListEmployeesWorkingTodayByRole(1);
    }

    public static function getListStorekeepersWorkingToday(): Collection
    {
        return self::getListEmployeesWorkingTodayByRole(2);
    }

    private static function getListEmployeesWorkingTodayByRole($roleId): Collection
    {
        return Schedule::query()
            ->where('date', now()->toDateString())
            ->whereHas('user', function ($query) use ($roleId) {
                $query->where('role_id', $roleId)
                    ->where('tg_id', '!=', null);
            })
            ->with('user')
            ->distinct()
            ->get()
            ->pluck('user.tg_id')
            ->unique();
    }

    public static function sendMessageForWorkingTodayEmployees(): void
    {
        $schedules = Schedule::query()
            ->where('date', now()->toDateString())
            ->with('user')
            ->distinct()
            ->get()
            ->unique();

        $list = '';
        foreach ($schedules as $schedule) {
            if ($schedule->user && $schedule->user->role) {
                $list .= '• ' . $schedule->user->name . ' ('.UserService::translateRoleName($schedule->user->role->name).')' . "\n";
            }
        }

        $text = "Сегодня работают: \n" . $list;

        foreach ($schedules as $schedule) {
            if ($schedule->user?->tg_id) {
                TgService::sendMessage($schedule->user->tg_id, $text);
            }
        }

        Log::channel('erp')->notice('В ТГ отправлено сообщение сотрудникам: ' . $text);
    }

    public static function getMotivationByUserId(mixed $id): \Illuminate\Database\Eloquent\Collection
    {
        return Motivation::query()
            ->where('user_id', $id)
            ->get();
    }

    public static function getRateByUserId(mixed $id): \Illuminate\Database\Eloquent\Collection
    {
        return Rate::query()
            ->where('user_id', $id)
            ->get();
    }

    public static function saved(Request $request, User $user): bool
    {
        $rules = [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'salary_rate' => 'sometimes|nullable|numeric',
            'password' => 'nullable|confirmed|string|min:6',
            'avatar' => 'sometimes|nullable|image|mimes:png|max:512|dimensions:width=256,height=256,ratio=1:1',
            'orders_priority' => 'string|in:all,fbo,fbo_200',
            'is_cutter' => 'boolean',
            'start_work_shift' => 'sometimes|date_format:H:i',
            'duration_work_shift' => 'sometimes|date_format:H:i|after_or_equal:00:00|before_or_equal:15:00',
            'max_late_minutes' => 'sometimes|numeric|min:0|max:180',
            'materials' => 'nullable|array|exists:materials,id',
        ];

        $validatedData = $request->validate($rules);

        if ($request->filled('password')) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        if ($request->hasFile('avatar')) {
            if (!Storage::disk('public')->exists('avatars')) {
                Storage::disk('public')->makeDirectory('avatars');
            }

            $fileName = $user->id . '.' . $request->file('avatar')
                    ->getClientOriginalExtension();

            $validatedData['avatar'] = $request->file('avatar')
                ->storeAs('avatars', $fileName, 'public');
        }

        if(auth()->user()->isAdmin()) {
            $user->materials()->sync($validatedData['materials'] ?? []);
        }

        return $user->update($validatedData);
    }

    public static function getUserByBarcode($barcode): ?User
    {
            $parts = explode('-', $barcode);
            $id = $parts[1] ?? null;

            $user = User::query()->find($id);
            if ($user) {
                return $user;
            }

        return null;
    }

    public static function checkWorkShiftClosure(User $user): void
    {
        $previousUserWhoClosedShift = User::query()
            ->where('shift_is_open', false)
            ->where('closed_work_shift', '!=', '00:00:00')
            ->where('id', '!=', $user->id)
            ->orderByDesc('closed_work_shift')
            ->first();

        if (!$previousUserWhoClosedShift) {
            return;
        }

        $closedTime = Carbon::createFromFormat('H:i:s', $previousUserWhoClosedShift->closed_work_shift)
            ->setDate(now()->year, now()->month, now()->day);

        $minutes = 2;

        if ($closedTime->diffInMinutes(now()) < $minutes) {
            $text = 'Внимание! Сотрудник ' . $user->name . ' (' . $user->id . ') ' .
                'пытался закрыть смену, сразу после ' . $previousUserWhoClosedShift->name . ' (' . $previousUserWhoClosedShift->id . ').';

            Log::channel('work_shift')->error($text);

            TgService::sendMessage(config('telegram.admin_id'), $text);
        }
    }

    public static function checkUnclosedWorkShifts(): void
    {
        $users = User::query()
            ->where('shift_is_open', true)
            ->get();

        $amount = Setting::getValue('unclosed_shift_penalty');
        $actualDate = now()->subDay();

        foreach ($users as $user) {
            Transaction::query()->create([
                'user_id' => $user->id,
                'title' => 'Штраф за незакрытую смену ' . $actualDate->format('d/m/Y'),
                'accrual_for_date' => $actualDate->format('Y-m-d'),
                'amount' => $amount,
                'transaction_type' => 'in',
                'status' => 1,
            ]);

            Log::channel('salary')->info(
                "Сотруднику $user->name (id $user->id) начислен штраф за незакрытую смену "
                . $actualDate->format('d/m/Y') . " в размере $amount бонусов."
            );

            $user->shift_is_open = false;
            $user->closed_work_shift = '00:00:00';
            $user->save();
        }
    }

    public static function checkLateStartWorkShift(User $user): void
    {
        $start_work_shift = Carbon::parse($user->start_work_shift);
        $maxLateTime = $start_work_shift->addMinutes($user->max_late_minutes);

        if ($maxLateTime->lessThan(now())) {
            $amount = Setting::getValue('late_opened_shift_penalty');
            $actualDate = now();

            Transaction::query()->create([
                'user_id' => $user->id,
                'title' => 'Штраф за опоздание на смену ' . $actualDate->format('d/m/Y'),
                'accrual_for_date' => $actualDate->format('Y-m-d'),
                'amount' => $amount,
                'transaction_type' => 'in',
                'status' => 1,
            ]);

            Log::channel('salary')->info(
                "Сотруднику $user->name (id $user->id) начислен штраф за опоздание за смену "
                . $actualDate->format('d/m/Y') . " в размере $amount бонусов."
            );
        }
    }
}
