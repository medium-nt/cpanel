<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MaxService
{
    /**
     * Отправляет сообщение в MAX мессенджер.
     *
     * В non-production только логирует (аналог TgService), в production выполняет
     * HTTP-запрос к MAX Bot API (https://platform-api2.max.ru/messages). Ошибки
     * сети и неудачные ответы логирует в канал 'max'.
     *
     * @param  string|null  $chatId  ID чата MAX получателя
     * @param  string  $text  Текст сообщения
     */
    public static function sendMessage(?string $chatId, string $text): void
    {
        if (! $chatId) {
            return;
        }

        //  если development сервер, то только логируем отправку.
        if (! app()->environment('production')) {
            Log::channel('max')->info('Отправляется сообщение... в MAX: '.$chatId.' : '.$text);

            return;
        }

        try {
            $response = Http::withHeaders(['Authorization' => config('services.max.token')])
                ->timeout(10)
                ->post(config('services.max.api_url').'/messages?chat_id='.$chatId, [
                    'text' => $text,
                ]);

            if ($response->failed()) {
                Log::channel('max')->error('chat_id: '.$chatId);
                Log::channel('max')->error('Ошибка MAX: HTTP '.$response->status().' '.$response->body());
            }
        } catch (\Throwable $e) {
            Log::channel('max')->error('chat_id: '.$chatId);
            Log::channel('max')->error('Ошибка MAX: '.$e->getMessage());
        }
    }
}
