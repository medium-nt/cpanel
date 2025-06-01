<?php

namespace App\Policies;

use App\Models\MarketplaceOrderItem;
use App\Models\User;

class MarketplaceOrderItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role->name == 'admin' || $user->role->name == 'seamstress' || $user->role->name == 'storekeeper';
    }

    public function getNew(User $user): bool
    {
        return $user->role->name == 'seamstress';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MarketplaceOrderItem $marketplaceOrderItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MarketplaceOrderItem $marketplaceOrderItem): bool
    {
        return $user->role->name === 'admin' || $user->role->name === 'seamstress';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MarketplaceOrderItem $marketplaceOrderItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MarketplaceOrderItem $marketplaceOrderItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MarketplaceOrderItem $marketplaceOrderItem): bool
    {
        return false;
    }
}
