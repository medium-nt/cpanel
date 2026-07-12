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
     * Dispatch через afterCommit(): если вызван внутри DB-транзакции, Job уйдёт в очередь
     * только после коммита (при rollback — отбрасывается); вне транзакции — сразу.
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
                $job = SendTelegramMessageJob::dispatch($user->tg_id, $text)->afterCommit();
                if ($delaySeconds !== null) {
                    $job->delay(now()->addSeconds($delaySeconds));
                }
            }
            if ($user->max_id) {
                $job = SendMaxMessageJob::dispatch($user->max_id, $text)->afterCommit();
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

    /**
     * Отправляет уведомление администратору во все каналы (Telegram + MAX) сразу на admin_id.
     *
     * При $queued=true (по умолчанию) отправка идёт через очереди (SendTelegramMessageJob /
     * SendMaxMessageJob) с опциональной задержкой $delaySeconds; иначе — синхронно через
     * TgService/MaxService. Канал пропускается тихо, если соответствующий admin_id не задан
     * в конфиге (config('telegram.admin_id') / config('services.max.admin_id')).
     * Dispatch через afterCommit(): см. note в notify() — критично для admin-точек внутри
     * DB-транзакций (MovementMaterialToWorkshopService и др.).
     *
     * @param  string  $text  Текст сообщения
     * @param  bool  $queued  Отправлять через очередь (true) или синхронно (false)
     * @param  int|null  $delaySeconds  Задержка отправки в секундах (только для $queued=true)
     */
    public static function notifyAdmin(string $text, bool $queued = true, ?int $delaySeconds = null): void
    {
        $tgAdmin = config('telegram.admin_id');
        $maxAdmin = config('services.max.admin_id');

        if ($queued) {
            if ($tgAdmin) {
                $job = SendTelegramMessageJob::dispatch($tgAdmin, $text)->afterCommit();
                if ($delaySeconds !== null) {
                    $job->delay(now()->addSeconds($delaySeconds));
                }
            }
            if ($maxAdmin) {
                $job = SendMaxMessageJob::dispatch($maxAdmin, $text)->afterCommit();
                if ($delaySeconds !== null) {
                    $job->delay(now()->addSeconds($delaySeconds));
                }
            }
        } else {
            if ($tgAdmin) {
                TgService::sendMessage($tgAdmin, $text);
            }
            if ($maxAdmin) {
                MaxService::sendMessage($maxAdmin, $text);
            }
        }
    }
}
