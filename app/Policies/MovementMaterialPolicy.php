<?php

namespace App\Policies;

use App\Models\MovementMaterial;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MovementMaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewAny_defect(User $user): bool
    {
        return $user->role->name === 'admin' || $user->role->name === 'storekeeper' || $user->role->name == 'cutter';
    }

    public function viewAny_remnants(User $user): bool
    {
        return $user->role->name === 'admin' || $user->role->name === 'storekeeper' || $user->role->name == 'cutter';
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
        return $user->role->name === 'admin' || $user->role->name === 'storekeeper';
    }

    public function create_remnants(User $user): bool
    {
        return $user->role->name === 'admin' || $user->role->name === 'storekeeper';
    }

    public function update(User $user): bool
    {
        return $user->role->name === 'admin' || $user->role->name === 'storekeeper' || $user->role->name === 'seamstress' || $user->role->name === 'cutter';
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
