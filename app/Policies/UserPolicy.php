<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Доступ к списку пользователей — только админ. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Просмотр профиля пользователя не используется (есть profile-страница). */
    public function view(User $user): bool
    {
        return false;
    }

    /** Создавать пользователя может только админ. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Редактировать пользователя может только админ. */
    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Удалять пользователя может только админ. */
    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Восстановление пользователя не поддерживается. */
    public function restore(User $user): bool
    {
        return false;
    }

    /** Окончательное удаление пользователя не поддерживается. */
    public function forceDelete(User $user): bool
    {
        return false;
    }
}
