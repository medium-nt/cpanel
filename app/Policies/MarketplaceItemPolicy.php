<?php

namespace App\Policies;

use App\Models\MarketplaceItem;
use App\Models\User;

class MarketplaceItemPolicy
{
    /** Доступ к списку товаров маркетплейса — только админ. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Просмотр отдельного товара не используется. */
    public function view(User $user, MarketplaceItem $marketplaceItem): bool
    {
        return false;
    }

    /** Создавать товар может только админ. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Редактировать товар может только админ. */
    public function update(User $user, MarketplaceItem $marketplaceItem): bool
    {
        return $user->isAdmin();
    }

    /** Удалять товар может только админ. */
    public function delete(User $user, MarketplaceItem $marketplaceItem): bool
    {
        return $user->isAdmin();
    }

    /** Восстановление товара не поддерживается. */
    public function restore(User $user, MarketplaceItem $marketplaceItem): bool
    {
        return false;
    }

    /** Окончательное удаление товара не поддерживается. */
    public function forceDelete(User $user, MarketplaceItem $marketplaceItem): bool
    {
        return false;
    }
}
