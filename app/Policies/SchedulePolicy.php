<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    /** Доступ к графику (schedule) через политику закрыт — регулируется маршрутом. */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /** Просмотр отдельной записи графика не используется. */
    public function view(User $user, Schedule $schedule): bool
    {
        return false;
    }

    /** Создание записи графика через политику закрыто. */
    public function create(User $user): bool
    {
        return false;
    }

    /** Перенести дату смены может только админ. */
    public function changeDate(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Редактирование записи графика через политику закрыто. */
    public function update(User $user, Schedule $schedule): bool
    {
        return false;
    }

    /** Удаление записи графика через политику закрыто. */
    public function delete(User $user, Schedule $schedule): bool
    {
        return false;
    }

    /** Восстановление записи графика не поддерживается. */
    public function restore(User $user, Schedule $schedule): bool
    {
        return false;
    }

    /** Окончательное удаление записи графика не поддерживается. */
    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return false;
    }
}
