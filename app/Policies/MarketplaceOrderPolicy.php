<?php

namespace App\Policies;

use App\Models\MarketplaceOrder;
use App\Models\User;

class MarketplaceOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return $user->role->name === 'admin';
    }

    public function update(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return $user->role->name === 'admin';
    }

    public function complete(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return $user->role->name === 'admin' || $user->role->name === 'seamstress';
    }

    public function delete(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return $user->role->name === 'admin';
    }

    public function restore(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return false;
    }

    public function forceDelete(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return false;
    }
}
