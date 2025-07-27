<?php

namespace App\Services;

use App\Models\Schedule;
use Illuminate\Support\Collection;

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
}
