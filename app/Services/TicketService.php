<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TicketService
{
    /**
     * Создать тикет от сотрудника: описание, URL проблемной страницы, скриншот.
     *
     * @param  array{description: string, page_url?: ?string}  $data
     */
    public function create(User $author, array $data, ?UploadedFile $screenshot = null): Ticket
    {
        return Ticket::query()->create([
            'user_id' => $author->id,
            'description' => $data['description'],
            'page_url' => $data['page_url'] ?? null,
            'screenshot' => $this->storeScreenshot($screenshot),
            'status' => Ticket::STATUS_NEW,
        ]);
    }

    /**
     * Перевести тикет в работу. Только для новых тикетов.
     */
    public function start(Ticket $ticket): bool
    {
        if ($ticket->status !== Ticket::STATUS_NEW) {
            return false;
        }

        $result = $ticket->markInProgress();
        Log::info('Тикет взят в работу администратором', [
            'ticket_id' => $ticket->id,
            'admin_id' => auth()->id(),
        ]);

        return $result;
    }

    /**
     * Закрыть тикет (перевести в статус «Закрыт») с обязательным комментарием админа.
     * Только для тикетов в работе.
     */
    public function close(Ticket $ticket, string $adminComment): bool
    {
        if ($ticket->status !== Ticket::STATUS_IN_PROGRESS || trim($adminComment) === '') {
            return false;
        }

        $result = $ticket->markClosed($adminComment);
        Log::info('Тикет закрыт администратором', [
            'ticket_id' => $ticket->id,
            'admin_id' => auth()->id(),
        ]);

        return $result;
    }

    /**
     * Отправить тикет в корзину (статус «В корзине»). Нельзя удалить повторно.
     */
    public function delete(Ticket $ticket): bool
    {
        if ($ticket->status === Ticket::STATUS_DELETED) {
            return false;
        }

        $result = $ticket->markDeleted();
        Log::info('Тикет отправлен в корзину администратором', [
            'ticket_id' => $ticket->id,
            'admin_id' => auth()->id(),
        ]);

        return $result;
    }

    /**
     * Сохранить скриншот в storage/app/public/tickets/, вернуть относительный путь.
     */
    private function storeScreenshot(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $fileName = now()->format('Ymd_His').'_'.uniqid().'.'.$file->getClientOriginalExtension();
        Storage::disk('public')->putFileAs('tickets', $file, $fileName);

        return 'tickets/'.$fileName;
    }
}
