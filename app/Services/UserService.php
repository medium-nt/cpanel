<?php

namespace App\Services;

class UserService
{
    public static function translateRoleName($role): string
    {
        match ($role) {
            'admin' => $roleName = 'Руководитель',
            'storekeeper' => $roleName = 'Кладовщик',
            'seamstress' => $roleName = 'Швея',
        };

        return $roleName;
    }
}
