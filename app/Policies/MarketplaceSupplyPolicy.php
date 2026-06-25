<?php

namespace App\Policies;

use App\Models\MarketplaceSupply;
use App\Models\User;

class MarketplaceSupplyPolicy
{
    /** Доступ к списку поставок: админ, кладовщик, водитель, менеджер. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isDriver() || $user->isManager();
    }

    /** Просмотр поставки: менеджер видит только FBO, остальные — админ, кладовщик, водитель. */
    public function view(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        if ($user->isManager()) {
            return $marketplaceSupply->type === 'FBO';
        }

        return $user->isAdmin() || $user->isStorekeeper() || $user->isDriver();
    }

    /** Создавать поставку могут админ, кладовщик, менеджер. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    /** Удалять поставку могут админ, кладовщик, менеджер. */
    public function destroy(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    /** Завершить поставку могут админ и кладовщик. */
    public function complete(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Закрыть поставку может только админ. */
    public function close(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin();
    }

    /** Откат отгрузки поставки — только админ. */
    public function unmarkShipped(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin();
    }

    /** Скачать видео отгрузки могут админ и кладовщик. */
    public function download_video(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Удалить видео отгрузки могут админ и кладовщик. */
    public function delete_video(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Привязать заявку WB FBO к поставке могут админ, кладовщик, менеджер. */
    public function linkWbFbo(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    /** Привязать заявку OZON FBO к поставке могут админ, кладовщик, менеджер. */
    public function linkOzonFbo(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    /** Редактировать FBO-параметры поставки могут админ и менеджер. */
    public function updateFbo(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /** Просматривать короба поставки могут админ, кладовщик, менеджер. */
    public function viewBoxes(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    /** Управлять коробами поставки могут админ и кладовщик. */
    public function manageBoxes(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Экспортировать короба могут админ, кладовщик, менеджер. */
    public function exportBoxes(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    /** Управлять стикерами поставки могут админ и менеджер. */
    public function manageSticker(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /** Скачать стикеры поставки могут админ, кладовщик, менеджер. */
    public function downloadSticker(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    /** Загружать/удалять накладную от Газельки могут админ и менеджер. */
    public function manageGazelkaInvoice(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    /** Скачать накладную от Газельки могут админ, кладовщик, менеджер. */
    public function downloadGazelkaInvoice(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    /** Удалять заказы внутри поставки может только админ. */
    public function deleteOrders(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin();
    }

    /** Отвязывать «не готовые» заказы от поставки может только админ. */
    public function detachOrders(User $user, MarketplaceSupply $marketplaceSupply): bool
    {
        return $user->isAdmin();
    }
}
