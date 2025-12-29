<?php

namespace App\Policies;

use App\Models\User;

class RollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function print(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }
}
