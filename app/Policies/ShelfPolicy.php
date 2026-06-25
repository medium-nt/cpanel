<?php

namespace App\Policies;

use App\Models\User;

class ShelfPolicy
{
    /** Доступ к списку полок: админ, кладовщик, менеджер. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper() || $user->isManager();
    }

    /** Просмотр полки: админ и кладовщик. */
    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Создавать полку может админ или кладовщик. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Создавать полку через админ-панель может только админ. */
    public function createAdmin(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Редактировать полку может админ или кладовщик. */
    public function update(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }

    /** Удалять полку может админ или кладовщик. */
    public function delete(User $user): bool
    {
        return $user->isAdmin() || $user->isStorekeeper();
    }
}
