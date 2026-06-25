<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    /** Доступ к списку поставщиков — только админ. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Просмотр отдельного поставщика не используется. */
    public function view(User $user, Supplier $supplier): bool
    {
        return false;
    }

    /** Создавать поставщика может только админ. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Редактировать поставщика может только админ. */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }

    /** Удалять поставщика может только админ. */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }

    /** Восстановление поставщика не поддерживается. */
    public function restore(User $user, Supplier $supplier): bool
    {
        return false;
    }

    /** Окончательное удаление поставщика не поддерживается. */
    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return false;
    }
}
