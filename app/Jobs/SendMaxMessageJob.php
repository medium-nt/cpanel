<?php

namespace App\Jobs;

use App\Services\MaxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMaxMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $chatId, public string $text) {}

    public function handle(): void
    {
        MaxService::sendMessage($this->chatId, $this->text);
        Log::channel('max')
            ->notice('Сообщение отправлено в MAX: '.$this->chatId.' с текстом: '.$this->text);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('max')
            ->error("Ошибка всех попыток отправки в MAX ({$this->chatId}): ".$exception->getMessage());
    }

    public function backoff(): array
    {
        // секунды между попытками: 1-я → 10с, 2-я → 30с, 3-я → 60с
        return [10, 30, 60];
    }
}
