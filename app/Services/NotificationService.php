<?php

namespace App\Services;

use App\Jobs\SendMaxMessageJob;
use App\Jobs\SendTelegramMessageJob;
use App\Models\User;

class NotificationService
{
    /**
     * Отправляет уведомление пользователю во все привязанные каналы (Telegram + MAX).
     *
     * Если у пользователя есть tg_id — отправляет в Telegram, если max_id — в MAX.
     * При $queued=true отправка идёт через очереди (SendTelegramMessageJob /
     * SendMaxMessageJob) с опциональной задержкой $delaySeconds; иначе — синхронно
     * через TgService/MaxService. Пользователь без обоих каналов пропускается тихо.
     *
     * @param  \App\Models\User  $user  Получатель уведомления
     * @param  string  $text  Текст сообщения
     * @param  bool  $queued  Отправлять через очередь (true) или синхронно (false)
     * @param  int|null  $delaySeconds  Задержка отправки в секундах (только для $queued=true)
     */
    public static function notify(User $user, string $text, bool $queued = false, ?int $delaySeconds = null): void
    {
        if ($queued) {
            if ($user->tg_id) {
                $job = SendTelegramMessageJob::dispatch($user->tg_id, $text);
                if ($delaySeconds !== null) {
                    $job->delay(now()->addSeconds($delaySeconds));
                }
            }
            if ($user->max_id) {
                $job = SendMaxMessageJob::dispatch($user->max_id, $text);
                if ($delaySeconds !== null) {
                    $job->delay(now()->addSeconds($delaySeconds));
                }
            }
        } else {
            if ($user->tg_id) {
                TgService::sendMessage($user->tg_id, $text);
            }
            if ($user->max_id) {
                MaxService::sendMessage($user->max_id, $text);
            }
        }
    }
}
