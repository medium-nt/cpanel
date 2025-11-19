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
        return $user->isSeamstress() || $user->isCutter();
    }

    public function update(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        $return = false;

        match ($order->status) {
            1 => $return = $user->isStorekeeper(),
            2 => $return = $user->isSeamstress() || $user->isCutter(),
            default => false
        };

        return $return;
    }

    public function approve_reject(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function pick_up(User $user, Order $order): bool
    {
        return $user->isStorekeeper();
    }

    public function collect(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function write_off(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
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
