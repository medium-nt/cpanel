<?php

namespace App\Policies;

use App\Models\ProductSticker;
use App\Models\User;

class ProductStickerPolicy
{
    /** Доступ к списку стикеров товара — только админ. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Просмотр отдельного стикера не используется. */
    public function view(User $user, ProductSticker $productSticker): bool
    {
        return false;
    }

    /** Создавать стикер может только админ. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Редактировать стикер может только админ. */
    public function update(User $user, ProductSticker $productSticker): bool
    {
        return $user->isAdmin();
    }

    /** Удалять стикер может только админ. */
    public function delete(User $user, ProductSticker $productSticker): bool
    {
        return $user->isAdmin();
    }

    /** Восстановление стикера не поддерживается. */
    public function restore(User $user, ProductSticker $productSticker): bool
    {
        return false;
    }

    /** Окончательное удаление стикера не поддерживается. */
    public function forceDelete(User $user, ProductSticker $productSticker): bool
    {
        return false;
    }
}
