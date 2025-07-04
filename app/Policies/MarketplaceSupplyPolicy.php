<?php

namespace App\Policies;

use App\Models\MarketplaceSupply;
use App\Models\User;

class MarketplaceSupplyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->name === 'admin' || $user->role->name == 'storekeeper';
    }

    public function view(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->role->name === 'admin' || $user->role->name == 'storekeeper';
    }

    public function create(User $user): bool
    {
        return $user->role->name === 'admin' || $user->role->name == 'storekeeper';
    }

    public function destroy(User $user): bool
    {
        return $user->role->name === 'admin' || $user->role->name == 'storekeeper';
    }

}
