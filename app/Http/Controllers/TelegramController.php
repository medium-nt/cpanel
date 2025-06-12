<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    protected $telegram;

    public function __construct()
    {
//        $this->telegram = new Api();
//        $this->telegram = new Api(config('telegram.bot_token'));
    }

    public function webhook(Request $request)
    {
        $update = $request->all();

        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];

            $client = new Client([
                'verify' => false,
                'timeout' => 30,
                'connect_timeout' => 30,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]
            ]);

            $client->post('https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN') . '/sendMessage', [
                'form_params' => [
                    'chat_id' => $chatId,
                    'text' => 'Привет! Я работаю!'
                ]
            ]);

            Log::channel('tg_api')->info('chat_id: ' . $chatId);

//            Telegram::sendMessage([
//                'chat_id' => $chatId,
//                'text' => 'Привет! Я работаю!'
//            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
