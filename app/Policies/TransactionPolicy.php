<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /** Доступ к финансовым операциям — только пользователям с правом просмотра финансов. */
    public function viewAny(User $user): bool
    {
        return $user->is_show_finance;
    }

    /** Просмотр отдельной операции не используется. */
    public function view(User $user, Transaction $transaction): bool
    {
        return false;
    }

    /** Создавать финансовую операцию может только админ. */
    public function create(User $user): bool
    {
        return auth()->user()->isAdmin();
    }

    /** Редактировать финансовую операцию может только админ. */
    public function update(User $user, Transaction $transaction): bool
    {
        return auth()->user()->isAdmin();
    }

    /** Удалять финансовую операцию может только админ. */
    public function delete(User $user, Transaction $transaction): bool
    {
        return auth()->user()->isAdmin();
    }

    /** Восстановление финансовой операции не поддерживается. */
    public function restore(User $user, Transaction $transaction): bool
    {
        return false;
    }

    /** Окончательное удаление финансовой операции не поддерживается. */
    public function forceDelete(User $user, Transaction $transaction): bool
    {
        return false;
    }
}
