<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MaxService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaxController extends Controller
{
    /**
     * Обрабатывает входящий webhook от MAX мессенджера.
     *
     * MAX присылает события типа message_created при любом сообщении боту.
     * Поддерживает два формата payload: одиночный Update (вебхук) и обёрнутый
     * массив {updates: [...]} (long-polling /updates). Для каждого сообщения
     * извлекает chat_id, ищет User по max_id и отправляет ссылку на профиль
     * (незарегистрированному) либо приветствие (зарегистрированному) — по
     * аналогии с TelegramController::webhook.
     *
     * @return array<string, string>
     */
    public function webhook(Request $request): array
    {
        $payload = $request->all();

        Log::channel('max')->info(json_encode($payload, JSON_UNESCAPED_UNICODE));

        // Webhook присылает одиночный Update, /updates — массив {updates: [...]}.
        $updates = $payload['updates'] ?? ($payload['message'] ?? null) ? [$payload] : [];

        foreach ($updates as $update) {
            $chatId = $update['message']['recipient']['chat_id'] ?? null;

            if ($chatId === null) {
                continue;
            }

            $chatId = (string) $chatId;

            $user = User::query()->where('max_id', $chatId)->first();

            if (! $user) {
                MaxService::sendMessage(
                    $chatId,
                    'Привет! Я бот компании Мегатюль. Для начала работы вы должны авторизоваться по этой ссылке: '
                    .url()->route('profile', ['max_id' => $chatId])
                );
            } else {
                MaxService::sendMessage(
                    $chatId,
                    'Привет, '.$user->name.'! Вы уже авторизованы в системе как '
                    .UserService::translateRoleName($user->role->name).
                    ' и теперь будете получать все уведомления системы через меня.'
                );
            }
        }

        return ['status' => 'ok'];
    }
}
