<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Клиент API доставки «Газелька» (gazelka.space).
 *
 * Эндпоинты собираются как {base_url}/{slug}. Авторизация — Bearer-токен
 * из config('services.gazelka.token'). Все методы возвращают декодированный
 * ответ (object) либо null при ошибке/неудачном HTTP-коде; ошибки пишутся
 * в канал логов marketplace_supplies.
 */
class GazelkaApiService
{
    /** Тип поставки (monomix): Моно */
    public const SUPPLY_TYPE_MONO = 1;

    /** Тип поставки (monomix): Микс */
    public const SUPPLY_TYPE_MIX = 2;

    /** Тип поставки (monomix): Суперсейф */
    public const SUPPLY_TYPE_SUPER_SAFE = 3;

    /** Тип поставки (monomix): КГТ */
    public const SUPPLY_TYPE_KGT = 4;

    /** Тип поставки (monomix): FBS */
    public const SUPPLY_TYPE_FBS = 5;

    /** Тип поставки (monomix): Транзит */
    public const SUPPLY_TYPE_TRANSIT = 6;

    /** Тип поставки (monomix): Питание */
    public const SUPPLY_TYPE_FOOD = 7;

    /** Город / склад: Иваново */
    public const CITY_IVANOVO = 1;

    /** Город / склад: Кострома */
    public const CITY_KOSTROMA = 2;

    /** Город / склад: Владимир */
    public const CITY_VLADIMIR = 3;

    /** Город / склад: Ярославль */
    public const CITY_YAROSLAVL = 4;

    /** Город / склад: Симферополь */
    public const CITY_SIMFEROPOL = 5;

    /** Тип оплаты (pricelist): Наличные */
    public const PAYMENT_CASH = 1;

    /** Тип оплаты (pricelist): Безнал */
    public const PAYMENT_CASHLESS = 2;

    /** Тип оплаты (pricelist): Безнал + НДС */
    public const PAYMENT_CASHLESS_VAT = 3;

    /**
     * Базовый HTTP-клиент с авторизацией и таймаутом.
     */
    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->withToken(config('services.gazelka.token'))
            ->timeout(config('services.gazelka.timeout', 30))
            ->withOptions(['verify' => config('services.gazelka.verify_ssl', false)]);
    }

    /**
     * Полный URL эндпоинта по slug.
     */
    private function url(string $slug): string
    {
        return rtrim((string) config('services.gazelka.base_url'), '/').'/'.$slug;
    }

    /**
     * Логирование ошибки в канал marketplace_supplies.
     */
    private function logError(string $message, ?Throwable $e = null): void
    {
        Log::channel('marketplace_supplies')->error('GazelkaApi: '.$message, [
            'exception' => $e?->getMessage(),
            'trace' => $e?->getTraceAsString(),
        ]);
    }

    /**
     * Обработать ответ: вернуть объект либо null с логом при неудачном коде.
     */
    private function handle(string $slug, Response $response): ?object
    {
        if (! $response->ok()) {
            $this->logError(sprintf('%s: HTTP %d — %s', $slug, $response->status(), (string) $response->body()));

            return null;
        }

        return $response->object();
    }

    /**
     * Справочник: статусы заявок и список маркетплейсов с ID.
     */
    public function descriptions(): ?object
    {
        try {
            return $this->handle('descriptions', $this->request()->get($this->url('descriptions')));
        } catch (Throwable $e) {
            $this->logError('descriptions: критическая ошибка', $e);

            return null;
        }
    }

    /**
     * График по городу.
     *
     * @param  int  $pricelistId  ID города (одна из self::CITY_*).
     */
    public function schedule(int $pricelistId): ?object
    {
        try {
            return $this->handle(
                'schedule',
                $this->request()->post($this->url('schedule'), ['pricelist_id' => $pricelistId])
            );
        } catch (Throwable $e) {
            $this->logError('schedule: критическая ошибка', $e);

            return null;
        }
    }

    /**
     * Создание новой заявки на доставку.
     *
     * @param array{
     *   pallets: int,
     *   boxes: int,
     *   cargo_pickup: int,
     *   palleting: int,
     *   departure_address: string,
     *   departure_date: string,
     *   departure_time: string,
     *   marketplace_id: int,
     *   place_id: int,
     *   delivery_date: string,
     *   monomix: int,
     *   notes: string,
     *   supply_id: int,
     *   weight2: int|float,
     *   length: int,
     *   width: int,
     *   height: int,
     * } $data
     * @return object|null Ответ вида {"status":"success","message":"Заявка N успешно создана!"}.
     */
    public function newPlan(array $data): ?object
    {
        try {
            return $this->handle('new-plan', $this->request()->post($this->url('new-plan'), $data));
        } catch (Throwable $e) {
            $this->logError('new-plan: критическая ошибка', $e);

            return null;
        }
    }

    /**
     * Удаление заявки по ID (должна принадлежать организации токена).
     */
    public function deletePlan(int $planId): ?object
    {
        try {
            return $this->handle(
                'delete-plan',
                $this->request()->post($this->url('delete-plan'), ['plan_id' => $planId])
            );
        } catch (Throwable $e) {
            $this->logError('delete-plan: критическая ошибка', $e);

            return null;
        }
    }

    /**
     * Запланированные и активные заявки организации.
     */
    public function myPlans(): ?object
    {
        try {
            return $this->handle('my-plans', $this->request()->post($this->url('my-plans')));
        } catch (Throwable $e) {
            $this->logError('my-plans: критическая ошибка', $e);

            return null;
        }
    }

    /**
     * Создание забора груза (объединение заявок).
     *
     * @param array{
     *   selected_plans: int[],
     *   pickup_town: string,
     *   pickup_street: string,
     *   pickup_date: string,
     *   pickup_time?: string,
     *   pickup_contact: string,
     * } $data
     * @return object|null Ответ вида {"status":"success","pickup_id":N,"plan_ids":[...]}.
     */
    public function createPickup(array $data): ?object
    {
        try {
            return $this->handle('create-pickup', $this->request()->post($this->url('create-pickup'), $data));
        } catch (Throwable $e) {
            $this->logError('create-pickup: критическая ошибка', $e);

            return null;
        }
    }

    /**
     * Добавление существующей заявки в уже созданный забор.
     *
     * @param  int  $pickupId  ID существующего забора.
     * @param  int  $planId  ID заявки.
     */
    public function addToPickup(int $pickupId, int $planId): ?object
    {
        try {
            return $this->handle(
                'add-to-pickup',
                $this->request()->post($this->url('add-to-pickup'), [
                    'pickup_id' => $pickupId,
                    'plan_id' => $planId,
                ])
            );
        } catch (Throwable $e) {
            $this->logError('add-to-pickup: критическая ошибка', $e);

            return null;
        }
    }

    /**
     * Удаление заявок из существующего забора.
     *
     * @param  int  $pickupId  ID забора.
     * @param  int[]  $plansToRemove  ID заявок для открепления.
     */
    public function removeFromPickup(int $pickupId, array $plansToRemove): ?object
    {
        try {
            return $this->handle(
                'remove-from-pickup',
                $this->request()->post($this->url('remove-from-pickup'), [
                    'pickup_id' => $pickupId,
                    'plans_to_remove' => $plansToRemove,
                ])
            );
        } catch (Throwable $e) {
            $this->logError('remove-from-pickup: критическая ошибка', $e);

            return null;
        }
    }

    /**
     * Прайслист: актуальные цены, направления и стоимости заборов.
     *
     * @param  int|null  $type  Тип оплаты (self::PAYMENT_*). По умолчанию 2 (Безнал).
     * @param  int|null  $weekday  День недели 1–7 (без передачи — сегодняшний день).
     */
    public function pricelist(?int $type = null, ?int $weekday = null): ?object
    {
        try {
            return $this->handle(
                'pricelist',
                $this->request()->post($this->url('pricelist'), array_filter([
                    'type' => $type,
                    'weekday' => $weekday,
                ], fn ($value) => $value !== null))
            );
        } catch (Throwable $e) {
            $this->logError('pricelist: критическая ошибка', $e);

            return null;
        }
    }
}
