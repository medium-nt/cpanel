<?php

namespace App\Http\Controllers;

use App\Services\TgService;
use Illuminate\Http\Request;

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

            TgService::sendMessage($chatId, 'Привет! Я работаю!');
        }

        return response()->json(['status' => 'ok']);
    }
}
