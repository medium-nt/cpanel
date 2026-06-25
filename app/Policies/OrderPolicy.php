<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /** Доступ к списку заказов на пошив через политику закрыт (регулируется маршрутом). */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /** Просмотр отдельного заказа на пошив не используется. */
    public function view(User $user, Order $order): bool
    {
        return false;
    }

    /** Создавать заказ на пошив могут швея и закройщик. */
    public function create(User $user): bool
    {
        return $user->isSeamstress() || $user->isCutter();
    }

    /** Редактирование: админ — всегда; по статусам заказа (1 — кладовщик, 2 — швея/закройщик/ОТК). */
    public function update(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        $return = false;

        match ($order->status) {
            1 => $return = $user->isStorekeeper(),
            2 => $return = $user->isSeamstress() || $user->isCutter() || $user->isOtk(),
            default => false
        };

        return $return;
    }

    /** Согласовать/отклонить заказ может только админ. */
    public function approve_reject(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    /** Забрать заказ в работу может кладовщик. */
    public function pick_up(User $user, Order $order): bool
    {
        return $user->isStorekeeper();
    }

    /** Комплектовать заказ могут админ и кладовщик. */
    public function collect(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Списать заказ может только админ. */
    public function write_off(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Удалить заказ может только админ. */
    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    /** Восстановление заказа не поддерживается. */
    public function restore(User $user, Order $order): bool
    {
        return false;
    }

    /** Окончательное удаление заказа не поддерживается. */
    public function forceDelete(User $user, Order $order): bool
    {
        return false;
    }
}
