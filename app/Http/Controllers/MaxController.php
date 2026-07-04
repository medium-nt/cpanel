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
     * извлекает chat_id, ищет User по max_id: незарегистрированному отправляет
     * ссылку на профиль, зарегистрированному — разбирает текст сообщения как
     * команду (поддерживается /users) либо отправляет приветствие.
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

                continue;
            }

            $text = trim((string) ($update['message']['body']['text'] ?? ''));

            if ($text === '/users') {
                $this->handleUsersCommand($user, $chatId);

                continue;
            }

            MaxService::sendMessage(
                $chatId,
                'Привет, '.$user->name.'! Вы уже авторизованы в системе как '
                .UserService::translateRoleName($user->role->name).
                ' и теперь будете получать все уведомления системы через меня.'
            );
        }

        return ['status' => 'ok'];
    }

    /**
     * Обрабатывает команду /users: отправляет список подключённых к MAX
     * пользователей (Фамилия И.О. — Роль). Доступ только у роли admin.
     */
    private function handleUsersCommand(User $user, string $chatId): void
    {
        if ($user->role->name !== 'admin') {
            return;
        }

        $lines = UserService::getConnectedToMaxUsers()
            ->map(fn (User $u) => sprintf(
                '%s — %s',
                $u->short_name,
                UserService::translateRoleName($u->role->name)
            ))
            ->all();

        $text = empty($lines)
            ? 'Нет подключённых пользователей.'
            : "Подключённые пользователи:\n".implode("\n", $lines);

        MaxService::sendMessage($chatId, $text);
    }
}
