<?php

namespace App\Policies;

use App\Models\User;

class RollPolicy
{
    /** Доступ к списку рулонов: админ и кладовщик. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Просмотр рулона: админ и кладовщик. */
    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Печать этикетки рулона: админ и кладовщик. */
    public function print(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Удалять рулон могут админ и кладовщик. */
    public function delete(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Вернуть рулон на склад может только админ. */
    public function returnToStorage(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Ручное списание метража рулона может только админ. */
    public function writeOff(User $user): bool
    {
        return $user->isAdmin();
    }
}
