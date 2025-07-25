<?php

namespace App\Services;

use GuzzleHttp\Client;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;

class TgService
{
    public static function sendMessage($chatId, $message): void
    {
        //  если development сервер, то отправляем сообщение в телеграм через guzzle.
        if (!app()->environment('production')) {
            $client = new Client([
                'verify' => false,
                'timeout' => 30,
                'connect_timeout' => 30,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]
            ]);

            $client->post('https://api.telegram.org/bot' . config('telegram.bots.mybot.token') . '/sendMessage', [
                'form_params' => [
                    'chat_id' => $chatId,
                    'text' => $message
                ]
            ]);
        } else {
            try {
                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => $message
                ]);
            } catch (TelegramSDKException $e) {
                Log::channel('tg_api')->error('chat_id: ' . $chatId);
                Log::channel('tg_api')->error('Ошибка Telegram: ' . $e->getMessage());
            }
        }
    }
}
