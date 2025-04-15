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
        return $user->role->name == 'seamstress';
    }

    public function update(User $user, Order $order): bool
    {
        match ($order->status) {
            0 => $return = $user->role->name == 'admin',
            1 => $return = $user->role->name == 'storekeeper',
            default => $return = false
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

    public function delete(User $user, Order $order): bool
    {
        return false;
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
