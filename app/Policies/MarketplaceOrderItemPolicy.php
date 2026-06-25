<?php

namespace App\Policies;

use App\Models\MarketplaceOrderItem;
use App\Models\User;

class MarketplaceOrderItemPolicy
{
    /** Доступ к позициям заказов доступен всем авторизованным. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Просмотр позиции заказа доступен всем авторизованным. */
    public function view(User $user): bool
    {
        return true;
    }

    /** Получить новую позицию в работу могут швея и закройщик. */
    public function getNew(User $user): bool
    {
        return $user->isSeamstress() || $user->isCutter();
    }

    /** Редактировать позицию могут админ, швея и закройщик. */
    public function update(User $user, MarketplaceOrderItem $marketplaceOrderItem): bool
    {
        return $user->isAdmin() || $user->isSeamstress() || $user->isCutter();
    }

    /** Печать позиции на A4 доступна закройщику и швее. */
    public function printA4(User $user): bool
    {
        return $user->isCutter() || $user->isSeamstress();
    }
}
