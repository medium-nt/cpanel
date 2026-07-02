<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Laravel\Facades\Telegram;

class TgService
{
    /** Cache-префикс: чат в саспенде/заблокирован (403). */
    private const CACHE_BANNED_PREFIX = 'tg:banned:';

    /** Cache-префикс: чал под rate-limit'ом (429). */
    private const CACHE_RATE_LIMITED_PREFIX = 'tg:rate_limited:';

    /** TTL флага бана (403), секунд. */
    private const TTL_BANNED = 21600;

    /** TTL флага rate-limit (429), секунд. */
    private const TTL_RATE_LIMITED = 1800;

    /**
     * Отправляет сообщение в Telegram с защитой Circuit Breaker.
     *
     * При 429 (rate-limit) и 403 (заблокирован/саспенд) выставляет Cache-флаг на
     * TTL_BANNED/TTL_RATE_LIMITED и тихо возвращает false, чтобы не плодить
     * запросы в забаненный API и не ронять 32 синхронные точки вызова. Флаги
     * проверяются до отправки — повторные вызовы skip'аются сразу.
     *
     * @param  string  $chatId  ID чата получателя
     * @param  string  $message  Текст сообщения
     * @return bool true — отправлено; false — пропущено (бан/лимит/ошибка)
     */
    public static function sendMessage($chatId, $message): bool
    {
        if (! $chatId) {
            return false;
        }

        if (Cache::has(self::CACHE_BANNED_PREFIX.$chatId)) {
            Log::channel('tg')
                ->error('чат остановлен по 403 (получили бан), chat_id='.$chatId);

            return false;
        }

        if (Cache::has(self::CACHE_RATE_LIMITED_PREFIX.$chatId)) {
            Log::channel('tg')
                ->error('отправка ограничена (слишком часто отправляем), chat_id='.$chatId);

            return false;
        }

        //  если development сервер, то отправляем сообщение в телеграм через guzzle.
        if (! app()->environment('production')) {
            Log::channel('tg')->info('Отправляется сообщение... в ТГ: '.$chatId.' : '.$message);

            return true;
        }

        try {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
            ]);

            return true;
        } catch (TelegramResponseException $e) {
            $status = $e->getHttpStatusCode();

            if ($status === 429) {
                Cache::put(self::CACHE_RATE_LIMITED_PREFIX.$chatId, true, self::TTL_RATE_LIMITED);
                Log::channel('tg')->warning('429 rate limit → circuit breaker on (chat_id='.$chatId.', ttl='.self::TTL_RATE_LIMITED.'s)');

                return false;
            }

            if ($status === 403) {
                Cache::put(self::CACHE_BANNED_PREFIX.$chatId, true, self::TTL_BANNED);
                Log::channel('tg')->warning('403 forbidden/banned → circuit breaker on (chat_id='.$chatId.', ttl='.self::TTL_BANNED.'s)');

                return false;
            }

            Log::channel('tg')->error('chat_id: '.$chatId);
            Log::channel('tg')->error('Ошибка Telegram (HTTP '.$status.'): '.$e->getMessage());

            return false;
        } catch (\Throwable $e) {
            Log::channel('tg')->error('chat_id: '.$chatId);
            Log::channel('tg')->error('Ошибка Telegram: '.$e->getMessage());

            return false;
        }
    }
}
