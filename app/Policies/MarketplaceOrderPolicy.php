<?php

namespace App\Policies;

use App\Models\MarketplaceOrder;
use App\Models\User;

class MarketplaceOrderPolicy
{
    /** Доступ к списку заказов: админ, кладовщик, менеджер. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    /** Просмотр отдельного заказа закрыт (работа идёт через список). */
    public function view(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return false;
    }

    /** Создавать заказ может админ или менеджер. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /** Редактировать заказ может только админ. */
    public function update(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return $user->isAdmin();
    }

    /** Завершить заказ может админ или кладовщик. */
    public function complete(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Удалить заказ может только админ. */
    public function delete(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return $user->isAdmin();
    }

    /** Снять заказ (убрать из обработки) может админ или кладовщик. */
    public function remove(User $user, MarketplaceOrder $marketplaceOrder): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }
}
