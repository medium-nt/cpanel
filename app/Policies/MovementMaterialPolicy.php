<?php

namespace App\Policies;

use App\Models\MovementMaterial;
use App\Models\User;

class MovementMaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewAny_defect(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isCutter();
    }

    public function viewAny_remnants(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isCutter();
    }

    public function view(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function create_defect(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function create_remnants(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter();
    }

    public function delete(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }

    public function restore(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }

    public function forceDelete(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }
}
