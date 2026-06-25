<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    /** Доступ к списку материалов — только админ. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Просмотр отдельного материала не используется. */
    public function view(User $user, Material $material): bool
    {
        return false;
    }

    /** Создавать материал может только админ. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Редактировать материал может только админ. */
    public function update(User $user, Material $material): bool
    {
        return $user->isAdmin();
    }

    /** Удалять материал может только админ. */
    public function delete(User $user, Material $material): bool
    {
        return $user->isAdmin();
    }

    /** Восстановление материала не поддерживается. */
    public function restore(User $user, Material $material): bool
    {
        return false;
    }

    /** Окончательное удаление материала не поддерживается. */
    public function forceDelete(User $user, Material $material): bool
    {
        return false;
    }
}
