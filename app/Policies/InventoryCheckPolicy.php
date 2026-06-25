<?php

namespace App\Policies;

use App\Models\User;

class InventoryCheckPolicy
{
    /** Доступ к списку инвентаризаций: админ и кладовщик. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Просмотр инвентаризации: админ и кладовщик. */
    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Создавать инвентаризацию может админ или кладовщик. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Редактировать инвентаризацию может админ или кладовщик. */
    public function update(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Удалять инвентаризацию может админ или кладовщик. */
    public function delete(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Восстановление инвентаризации не поддерживается. */
    public function restore(User $user): bool
    {
        return false;
    }

    /** Окончательное удаление инвентаризации не поддерживается. */
    public function forceDelete(User $user): bool
    {
        return false;
    }
}
