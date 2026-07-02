<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MaxService
{
    /** Cache-префикс: чат в саспенде (403 dialog.suspended). */
    private const CACHE_BANNED_PREFIX = 'max:banned:';

    /** Cache-префикс: чат под rate-limit'ом (429). */
    private const CACHE_RATE_LIMITED_PREFIX = 'max:rate_limited:';

    /** TTL флага бана (403), секунд. */
    private const TTL_BANNED = 21600;

    /** TTL флага rate-limit (429), секунд. */
    private const TTL_RATE_LIMITED = 1800;

    /**
     * Отправляет сообщение в MAX мессенджер с защитой Circuit Breaker.
     *
     * В non-production только логирует (аналог TgService), в production выполняет
     * HTTP-запрос к MAX Bot API (https://platform-api2.max.ru/messages). При 429
     * (too.many.requests) и 403 (dialog.suspended) выставляет Cache-флаг и тихо
     * возвращает false, чтобы не плодить запросы в забаненный API. Повторные
     * вызовы skip'аются по флагу ещё до HTTP-запроса.
     *
     * @param  string|null  $chatId  ID чата MAX получателя
     * @param  string  $text  Текст сообщения
     * @return bool true — отправлено; false — пропущено (бан/лимит/ошибка/null chat)
     */
    public static function sendMessage(?string $chatId, string $text): bool
    {
        if (! $chatId) {
            return false;
        }

        if (Cache::has(self::CACHE_BANNED_PREFIX.$chatId)) {
            Log::channel('max')
                ->error('чат остановлен по 403 (получили бан), chat_id='.$chatId);

            return false;
        }

        if (Cache::has(self::CACHE_RATE_LIMITED_PREFIX.$chatId)) {
            Log::channel('max')
                ->error('отправка ограничена (слишком часто отправляем), chat_id='.$chatId);

            return false;
        }

        //  если development сервер, то только логируем отправку.
        if (! app()->environment('production')) {
            Log::channel('max')->info('Отправляется сообщение... в MAX: '.$chatId.' : '.$text);

            return true;
        }

        try {
            $response = Http::withHeaders(['Authorization' => config('services.max.token')])
                ->withOptions(['verify' => config('services.max.verify_ssl', true)])
                ->timeout(10)
                ->post(config('services.max.api_url').'/messages?chat_id='.$chatId, [
                    'text' => $text,
                ]);

            if ($response->failed()) {
                return self::handleFailedResponse($response, $chatId);
            }

            return true;
        } catch (\Throwable $e) {
            Log::channel('max')->error('chat_id: '.$chatId);
            Log::channel('max')->error('Ошибка MAX: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Разбирает неудачный ответ MAX API: взводит Circuit Breaker при 429/403.
     *
     * @return bool всегда false (отправка не удалась)
     */
    private static function handleFailedResponse($response, string $chatId): bool
    {
        $status = $response->status();
        $json = $response->json() ?? [];
        $code = $json['code'] ?? null;
        $message = $json['message'] ?? '';

        if ($status === 429 || $code === 'too.many.requests') {
            Cache::put(self::CACHE_RATE_LIMITED_PREFIX.$chatId, true, self::TTL_RATE_LIMITED);
            Log::channel('max')->warning('429 rate limit → circuit breaker on (chat_id='.$chatId.', ttl='.self::TTL_RATE_LIMITED.'s)');

            return false;
        }

        if ($status === 403 && str_contains((string) $message, 'dialog.suspended')) {
            Cache::put(self::CACHE_BANNED_PREFIX.$chatId, true, self::TTL_BANNED);
            Log::channel('max')->warning('403 dialog.suspended → circuit breaker on (chat_id='.$chatId.', ttl='.self::TTL_BANNED.'s)');

            return false;
        }

        Log::channel('max')->error('chat_id: '.$chatId);
        Log::channel('max')->error('Ошибка MAX: HTTP '.$status.' '.$response->body());

        return false;
    }
}
