<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Order $order): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return $user->role->name == 'seamstress' || $user->role->name == 'cutter';
    }

    public function update(User $user, Order $order): bool
    {
        if ($user->role->name == 'admin'){
            return true;
        }
        $return = false;

        match ($order->status) {
            1 => $return = $user->role->name == 'storekeeper',
            2 => $return = $user->role->name == 'seamstress' || $user->role->name == 'cutter',
            default => false
        };

        return $return;
    }

    public function approve_reject(User $user, Order $order): bool
    {
        return $user->role->name == 'admin';
    }

    public function pick_up(User $user, Order $order): bool
    {
        return $user->role->name == 'storekeeper';
    }

    public function collect(User $user, Order $order): bool
    {
        return $user->role->name == 'storekeeper';
    }

    public function write_off(User $user): bool
    {
        return $user->role->name == 'admin';
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->role->name == 'admin';
    }

    public function restore(User $user, Order $order): bool
    {
        return false;
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return false;
    }
}
