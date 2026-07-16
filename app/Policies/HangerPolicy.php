<?php

namespace App\Policies;

use App\Models\User;

class HangerPolicy
{
    /** Доступ к списку вешалок: только админ. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Просмотр вешалки: только админ. */
    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Создавать вешалку может только админ. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Редактировать вешалку может только админ. */
    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Удалять вешалку может только админ. */
    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }
}
