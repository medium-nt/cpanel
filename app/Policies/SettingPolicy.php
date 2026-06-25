<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    /** Доступ к настройкам — только админ. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Изменять настройки может только админ. */
    public function update(User $user): bool
    {
        return $user->isAdmin();
    }
}
