<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TgService
{
    public static function sendMessage($chatId, $message): void
    {
        if (! $chatId) {
            return;
        }

        //  если development сервер, то отправляем сообщение в телеграм через guzzle.
        if (! app()->environment('production')) {
            Log::channel('tg')->info('Отправляется сообщение... в ТГ: '.$chatId.' : '.$message);
        } else {
            try {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => $message,
                ]);
            } catch (\Throwable $e) {
                Log::channel('tg')->error('chat_id: '.$chatId);
                Log::channel('tg')->error('Ошибка Telegram: '.$e->getMessage());
            }
        }
    }
}
