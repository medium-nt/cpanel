<?php

namespace App\Jobs;

use App\Services\TgService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $chatId, public string $text) {}

    public function handle(): void
    {
        if (TgService::sendMessage($this->chatId, $this->text)) {
            Log::channel('tg')
                ->notice('Сообщение отправлено в ТГ: '.$this->chatId.' с текстом: '.$this->text);

            return;
        }

        // sendMessage вернул false — Circuit Breaker (429/403) или нет chat_id.
        // Retry не делаем: флаг уже взведён внутри сервиса, повторы лишь усилили бы бан.
        Log::channel('tg')
            ->warning('Сообщение в ТГ пропущено (circuit breaker / нет chat_id): '.$this->chatId);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('tg')
            ->error("Ошибка всех попыток отправки в ТГ ({$this->chatId}): ".$exception->getMessage());
    }

    public function backoff(): array
    {
        // секунды между попытками: 1-я → 10с, 2-я → 30с, 3-я → 60с
        return [10, 30, 60];
    }
}
