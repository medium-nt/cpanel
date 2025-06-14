<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    protected $telegram;

    public function __construct()
    {

    }

    public function webhook(Request $request)
    {
        $update = $request->all();

        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];

            self::sendMessage($chatId, 'Твое сообщение: ' . $message['text']);
        }

        return response()->json(['status' => 'ok']);
    }

    private static function sendMessage($chatId, $message): void
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
                    'text' => 'Привет! Я работаю!'
                ]
            ]);
        } else {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message
            ]);
        }
    }
}
