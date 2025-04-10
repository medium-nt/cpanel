<?php

namespace App\Policies;

use App\Models\MaterialConsumption;
use App\Models\User;

class MaterialConsumptionPolicy
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
    public function view(User $user, MaterialConsumption $materialConsumption): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role->name == 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MaterialConsumption $materialConsumption): bool
    {
        return $user->role->name == 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MaterialConsumption $materialConsumption): bool
    {
        return $user->role->name == 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MaterialConsumption $materialConsumption): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MaterialConsumption $materialConsumption): bool
    {
        return false;
    }
}
