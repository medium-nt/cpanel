<?php

namespace App\Services;

use App\Models\Motivation;
use App\Models\Rate;
use App\Models\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class UserService
{
    public static function translateRoleName($role): string
    {
        $roleName = '---';
        match ($role) {
            'admin' => $roleName = 'Руководитель',
            'storekeeper' => $roleName = 'Кладовщик',
            'seamstress' => $roleName = 'Швея',
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
}
