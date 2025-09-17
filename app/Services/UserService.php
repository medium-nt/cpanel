<?php

namespace App\Services;

use App\Models\Motivation;
use App\Models\Rate;
use App\Models\Schedule;
use App\Models\User;
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
            if ($schedule->user->tg_id) {
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
            'number_working_hours' => 'sometimes|integer|numeric|min:0|max:16',
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
}
