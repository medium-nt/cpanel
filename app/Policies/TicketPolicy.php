<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Список тикетов: доступен любому аутентифицированному сотруднику (видит свои/все).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Просмотр тикета: только автор или администратор.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id || $user->isAdmin();
    }

    /**
     * Создание тикета доступно любому сотруднику.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Перевод тикета в работу: только администратор и только тикет в статусе «Новый».
     */
    public function start(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() && $ticket->status === Ticket::STATUS_NEW;
    }

    /**
     * Закрытие тикета: только администратор и только тикет в статусе «В работе».
     */
    public function close(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() && $ticket->status === Ticket::STATUS_IN_PROGRESS;
    }

    /**
     * Отправка тикета в корзину: только администратор; нельзя удалить уже удалённый.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() && $ticket->status !== Ticket::STATUS_DELETED;
    }
}
