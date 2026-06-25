<?php

namespace App\Policies;

use App\Models\MovementMaterial;
use App\Models\User;

class MovementMaterialPolicy
{
    /** Доступ к списку движений материала: все производственные роли. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter() || $user->isManager() || $user->isOtk();
    }

    /** Доступ к списку брака: админ, кладовщик, закройщик. */
    public function viewAny_defect(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isCutter();
    }

    /** Доступ к списку остатков: админ, кладовщик, закройщик. */
    public function viewAny_remnants(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isCutter();
    }

    /** Просмотр отдельного движения не используется. */
    public function view(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }

    /** Создавать движение могут админ, кладовщик, швея, закройщик, ОТК. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter() || $user->isOtk();
    }

    /** Создавать движение брака могут админ и кладовщик. */
    public function create_defect(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Создавать движение остатков могут админ и кладовщик. */
    public function create_remnants(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Редактировать движение могут админ, кладовщик, швея, закройщик, ОТК. */
    public function update(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter() || $user->isOtk();
    }

    /** Удаление движения закрыто. */
    public function delete(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }

    /** Восстановление движения не поддерживается. */
    public function restore(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }

    /** Окончательное удаление движения не поддерживается. */
    public function forceDelete(User $user, MovementMaterial $movementMaterial): bool
    {
        return false;
    }
}
