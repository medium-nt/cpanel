<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Shelf;

class ShelfPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Shelf $shelf): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Shelf $shelf): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Shelf $shelf): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }
}
