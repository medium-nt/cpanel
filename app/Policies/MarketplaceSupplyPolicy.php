<?php

namespace App\Policies;

use App\Models\MarketplaceSupply;
use App\Models\User;

class MarketplaceSupplyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function view(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function destroy(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function complete(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function download_video(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function delete_video(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

}
