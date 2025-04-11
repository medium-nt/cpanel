<?php

namespace App\Policies;

use App\Models\MovementMaterial;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MovementMaterialPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role->name === 'admin' || $user->role->name === 'storekeeper' || $user->role->name === 'seamstress';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return $user->role->name === 'admin' || $user->role->name === 'storekeeper' || $user->role->name === 'seamstress';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }
}
