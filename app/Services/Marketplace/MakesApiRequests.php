<?php

namespace App\Services\Marketplace;

use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * HTTP-обвязка для запросов к API маркетплейсов Ozon и Wildberries.
 *
 * Содержит базовые фабрики {@see PendingRequest} с подстановкой ключей
 * из настроек и геттеры этих ключей. Подключается в статические
 * stateless-сервисы интеграции с маркетплейсами.
 */
trait MakesApiRequests
{
    /**
     * Получает WB API-ключ из настроек системы.
     */
    protected static function getWbApiKey()
    {
        return Setting::query()->where('name', 'api_key_wb')->first()->value;
    }

    /**
     * Получает Ozon API-ключ из настроек системы.
     */
    protected static function getOzonApiKey()
    {
        return Setting::query()->where('name', 'api_key_ozon')->first()->value;
    }

    /**
     * Получает Ozon Seller ID из настроек системы.
     */
    protected static function getOzonSellerId()
    {
        return Setting::query()->where('name', 'seller_id_ozon')->first()->value;
    }

    /**
     * Создаёт HTTP-клиент для запросов к Ozon API с подстановкой Seller ID и API-ключа.
     */
    protected static function ozonRequest(): PendingRequest
    {
        return Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ]);
    }

    /**
     * Создаёт HTTP-клиент для запросов к WB API с подстановкой токена авторизации.
     */
    protected static function wbRequest(): PendingRequest
    {
        return Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders(['Authorization' => self::getWbApiKey()]);
    }
}
