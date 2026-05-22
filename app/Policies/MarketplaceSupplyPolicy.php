<?php

namespace App\Policies;

use App\Models\MarketplaceSupply;
use App\Models\User;

class MarketplaceSupplyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isDriver() || $user->isManager();
    }

    public function view(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        if ($user->isManager()) {
            return $marketplaceSupply->type === 'FBO';
        }

        return $user->isAdmin() || $user->isStorekeeper() || $user->isDriver();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    public function destroy(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    public function complete(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function close(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin();
    }

    public function download_video(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function delete_video(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function linkWbFbo(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    public function updateWbFbo(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function viewBoxes(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    public function manageBoxes(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    public function exportBoxes(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    public function manageSticker(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function downloadSticker(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }
}
