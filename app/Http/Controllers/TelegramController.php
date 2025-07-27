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

            Log::channel('tg_api')->info('tgId: ' . $tgId);
            Log::channel('tg_api')->info('user: ' . json_encode($user));

            if (!$user) {
                TgService::sendMessage(
                    $tgId,
                    'Привет! Я бот компании Мегатюль. Для начала работы вы должны авторизоваться по этой ссылке: ' . route('users.login', ['tgId' => $tgId])
                );
            } else {
                TgService::sendMessage(
                    $tgId,
                    'Привет, ' . $user->name . '! Вы уже авторизованы в системе как ' . $user->role->name . ' и теперь будете получать все уведомления системы через меня.'
                );
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
