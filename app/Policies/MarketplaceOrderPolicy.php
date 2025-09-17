<?php

namespace App\Policies;

use App\Models\MarketplaceOrder;
use App\Models\User;

class MarketplaceOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function view(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return $user->isAdmin();
    }

    public function complete(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function delete(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return $user->isAdmin();
    }

    public function remove(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }
}
