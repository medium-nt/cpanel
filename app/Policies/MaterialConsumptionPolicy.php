<?php

namespace App\Policies;

use App\Models\MaterialConsumption;
use App\Models\User;

class MaterialConsumptionPolicy
{
    /** Список списаний материала доступен всем. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Просмотр отдельного списания не используется. */
    public function view(User $user, MaterialConsumption $materialConsumption): bool
    {
        return false;
    }

    /** Создавать списание может только админ. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Редактировать списание может только админ. */
    public function update(User $user, MaterialConsumption $materialConsumption): bool
    {
        return $user->isAdmin();
    }

    /** Удалять списание может только админ. */
    public function delete(User $user, MaterialConsumption $materialConsumption): bool
    {
        return $user->isAdmin();
    }

    /** Восстановление списания не поддерживается. */
    public function restore(User $user, MaterialConsumption $materialConsumption): bool
    {
        return false;
    }

    /** Окончательное удаление списания не поддерживается. */
    public function forceDelete(User $user, MaterialConsumption $materialConsumption): bool
    {
        return false;
    }
}
