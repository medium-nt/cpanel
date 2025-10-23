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
        return true;
    }

    public function getNew(User $user): bool
    {
        return $user->isSeamstress() || $user->isCutter();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MarketplaceOrderItem $marketplaceOrderItem): bool
    {
        return $user->isAdmin() || $user->isSeamstress() || $user->isCutter();
    }

    public function printCutting(User $user): bool
    {
        return $user->isCutter();
    }
}
