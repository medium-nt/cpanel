<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\TgService;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $chatId, public string $text) {}

    public function handle(): void
    {
        TgService::sendMessage($this->chatId, $this->text);
        Log::channel('queue')
            ->notice('Сообщение отправлено в ТГ: ' . $this->chatId . ' с текстом: ' . $this->text);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('queue')
            ->error("Ошибка всех попыток отправки в ТГ ({$this->chatId}): " . $exception->getMessage());
    }

    public function backoff(): array
    {
        // секунды между попытками: 1-я → 10с, 2-я → 30с, 3-я → 60с
        return [10, 30, 60];
    }
}
