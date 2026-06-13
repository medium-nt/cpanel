<?php

namespace App\Services;

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
            'otk' => $roleName = 'Сотрудник ОТК',
            'driver' => $roleName = 'Водитель',
            'manager' => $roleName = 'Менеджер маркетплейса',
            default => $roleName,
        };

        return $roleName;
    }

    /**
     * Получить tg_id работающих сегодня швей (опционально по цеху).
     */
    public static function getListSeamstressesWorkingToday(?int $workshopId = null): Collection
    {
        return self::getListEmployeesWorkingTodayByRole(1, $workshopId);
    }

    public static function getListStorekeepersWorkingToday(): Collection
    {
        return self::getListEmployeesWorkingTodayByRole(2);
    }

    /**
     * Возвращает коллекцию tg_id всех активных менеджеров с привязанным Telegram.
     */
    public static function getListManagersWithTg(): Collection
    {
        return User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'manager'))
            ->whereNotNull('tg_id')
            ->pluck('tg_id');
    }

    /**
     * Получить tg_id работающих сегодня сотрудников по роли (опционально по цеху).
     */
    private static function getListEmployeesWorkingTodayByRole($roleId, ?int $workshopId = null): Collection
    {
        $users = Schedule::query()
            ->where('date', now()->toDateString())
            ->whereHas('user', function ($query) use ($roleId) {
                $query->where('role_id', $roleId)
                    ->where('tg_id', '!=', null);
            })
            ->with('user')
            ->distinct()
            ->get()
            ->pluck('user')
            ->unique();

        // Фильтр по цеху: оставляем только сотрудников, чья текущая смена в указанном цехе
        if ($workshopId !== null) {
            $users = $users->filter(fn (User $user) => $user->currentWorkshop()?->id === $workshopId);
        }

        return $users->pluck('tg_id');
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
                $list .= '• '.$schedule->user->name.' ('.UserService::translateRoleName($schedule->user->role->name).')'."\n";
            }
        }

        $text = "Сегодня работают: \n".$list;

        foreach ($schedules as $schedule) {
            if ($schedule->user?->tg_id) {
                TgService::sendMessage($schedule->user->tg_id, $text);
            }
        }

        Log::channel('work_shift')->notice('В ТГ отправлено сообщение сотрудникам: '.$text);
    }

    public static function hasUnpaidSalary(User $user): bool
    {
        return Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('paid_at')
            ->exists();
    }

    public static function saved(Request $request, User $user): bool
    {
        $rules = [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'sometimes|nullable|string|min:8|max:50',
            'password' => 'nullable|confirmed|string|min:6',
            'avatar' => 'sometimes|nullable|image|mimes:png|max:512|dimensions:width=256,height=256,ratio=1:1',
            'orders_priority' => 'string|in:all,fbo,fbo_200',
            'is_cutter' => 'boolean',
            'start_work_shift' => 'sometimes|date_format:H:i',
            'duration_work_shift' => 'sometimes|date_format:H:i|after_or_equal:00:00|before_or_equal:15:00',
            'max_late_minutes' => 'sometimes|numeric|min:0|max:180',
            'materials' => 'nullable|array|exists:materials,id',
            'is_show_finance' => 'boolean',
        ];

        $validatedData = $request->validate($rules);

        if ($request->filled('password')) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        if ($request->hasFile('avatar')) {
            if (! Storage::disk('public')->exists('avatars')) {
                Storage::disk('public')->makeDirectory('avatars');
            }

            $fileName = $user->id.'.'.$request->file('avatar')
                ->getClientOriginalExtension();

            $validatedData['avatar'] = $request->file('avatar')
                ->storeAs('avatars', $fileName, 'public');
        }

        if (auth()->user()->isAdmin()) {
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

        if (! $previousUserWhoClosedShift) {
            return;
        }

        $closedTime = Carbon::createFromFormat('H:i:s', $previousUserWhoClosedShift->closed_work_shift)
            ->setDate(now()->year, now()->month, now()->day);

        $minutes = 2;

        if ($closedTime->diffInMinutes(now()) < $minutes) {
            $text = 'Внимание! Сотрудник '.$user->name.' ('.$user->id.') '.
                'пытался закрыть смену, сразу после '.$previousUserWhoClosedShift->name.' ('.$previousUserWhoClosedShift->id.').';

            Log::channel('work_shift')->error($text);

            TgService::sendMessage(config('telegram.admin_id'), $text);
        }
    }

    /**
     * Проверяет незакрытые смены: начисляет штраф сотрудникам и закрывает смены.
     * (админы освобождены от штрафа).
     */
    public static function checkUnclosedWorkShifts(): void
    {
        $users = User::query()
            ->where('shift_is_open', true)
            ->get();

        $amount = Setting::getValue('unclosed_shift_penalty');
        $actualDate = now()->subDay();

        foreach ($users as $user) {
            if (! $user->isAdmin()) {
                Transaction::query()->create([
                    'user_id' => $user->id,
                    'title' => 'Штраф за незакрытую смену '.$actualDate->format('d/m/Y'),
                    'accrual_for_date' => $actualDate->format('Y-m-d'),
                    'amount' => $amount,
                    'transaction_type' => 'in',
                    'status' => 1,
                ]);

                Log::channel('salary')->info(
                    "Сотруднику $user->name (id $user->id) начислен штраф за незакрытую смену "
                    .$actualDate->format('d/m/Y')." в размере $amount бонусов."
                );
            }

            $user->shift_is_open = false;
            $user->closed_work_shift = '00:00:00';
            $user->save();
        }
    }

    /**
     * Проверяет опоздание сотрудника на смену и начисляет штраф.
     * (админы освобождены от штрафа).
     */
    public static function checkLateStartWorkShift(User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $start_work_shift = Carbon::parse($user->start_work_shift);
        $maxLateTime = $start_work_shift->addMinutes($user->max_late_minutes);

        if ($maxLateTime->lessThan(now())) {
            $amount = Setting::getValue('late_opened_shift_penalty');
            $actualDate = now();

            Transaction::query()->create([
                'user_id' => $user->id,
                'title' => 'Штраф за опоздание на смену '.$actualDate->format('d/m/Y'),
                'accrual_for_date' => $actualDate->format('Y-m-d'),
                'amount' => $amount,
                'transaction_type' => 'in',
                'status' => 1,
            ]);

            Log::channel('salary')->info(
                "Сотруднику $user->name (id $user->id) начислен штраф за опоздание за смену "
                .$actualDate->format('d/m/Y')." в размере $amount бонусов."
            );
        }
    }

    public static function clearTimeForClosedWorkShifts(): void
    {
        User::query()
            ->where('shift_is_open', false)
            ->where('closed_work_shift', '!=', '00:00:00')
            ->update([
                'closed_work_shift' => '00:00:00',
            ]);
    }

    public static function isSecondShiftOpeningToday(User $user): bool
    {
        return Schedule::query()
            ->where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->where('shift_opened_time', '!=', '00:00:00')
            ->exists();
    }
}
