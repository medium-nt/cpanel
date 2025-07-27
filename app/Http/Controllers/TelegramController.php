<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TgService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    protected $telegram;

    public function __construct()
    {

    }

    public function webhook(Request $request)
    {
        $update = $request->all();

        Log::channel('tg_api')->info(json_encode($update));

        if (isset($update['message'])) {
            $message = $update['message'];
            $tgId = $message['chat']['id'];
            $text = $message['text'];

            $user = User::query()->where('tg_id', $tgId)->first();

            if (!$user) {
                Log::channel('tg_api')->info('user: ' . json_encode($user));
                Log::channel('tg_api')->info('user: нету');

                TgService::sendMessage(
                    $tgId,
                    'Привет! Я бот компании Мегатюль. Для начала работы вы должны авторизоваться по этой ссылке: '
                );
            } else {
                Log::channel('tg_api')->info('user: ' . json_encode($user));
                Log::channel('tg_api')->info('user: есть');
                TgService::sendMessage(
                    $tgId,
                    'Привет, ' . $user->name . '! Вы уже авторизованы в системе как ' . $user->role->name . ' и теперь будете получать все уведомления системы через меня.'
                );
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
