<?php

namespace App\Services;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Setting;
use App\Models\Sku;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketplaceApiService
{
    public static function getItemsWb($cursor = 0): object|false|null
    {
        $response = Http::accept('application/json')
        ->withOptions(['verify' => false])
        ->withHeaders(['Authorization' => self::getWbApiKey()])
        ->post('https://content-api.wildberries.ru/content/v2/get/cards/list', $cursor);

        if(!$response->ok()) {
            return false;
        }

        return $response->object();
    }

    public static function getAllItemsWb(): array
    {
        $productsArray = [];

        $limit = 100;
        $cursor = [
            "settings" => [
                "cursor" => [
                    "limit" => $limit
                ],
                "filter" => [
                    "withPhoto" => -1
                ]
            ]
        ];

        do {
            $items = MarketplaceApiService::getItemsWb($cursor);

            if(!$items) {
                return $productsArray;
            }

            foreach ($items->cards as $product) {
                $array = [
                    'marketplace_id' => 'wb',
                    'title' => $product->title,
                    'nmID' => $product->nmID,
                    'skus' => [],
                ];

                foreach ($product->sizes as $size) {
                    $array['skus'][] = $size->skus[0] ?? '';
                }

                $productsArray[] = $array;
            }


            if (isset($items->cursor->total) && $items->cursor->total >= $limit) {
                $cursor["settings"]["cursor"]["updatedAt"] = $items->cursor->updatedAt;
                $cursor["settings"]["cursor"]["nmID"] = $items->cursor->nmID;
            } else {
                break;
            }
        } while (true);

        return $productsArray;
    }

    public static function getAllItemsOzon(): array
    {
        $productsArray = [];
        $lastId = '';

        $limit = 100;
        $body = [
            "filter" => [
                "visibility" => "ALL",
            ],
            "limit" => $limit,
            "sort_dir" => "ASC",
            "last_id" => $lastId,
        ];

        do {
            $items = MarketplaceApiService::getItemsOzon($body);

            if(!$items) {
                return $productsArray;
            }

            foreach ($items->result as $product) {
                $array = [
                    'marketplace_id' => 'ozon',
                    'title' => $product->name,
                    'nmID' => $product->id,
                    'skus' => [],
                ];

                $array['skus'][] = $product->sku;

                $productsArray[] = $array;
            }

            if (isset($items->last_id)) {
                $body["last_id"] = $items->last_id;
            } else {
                break;
            }
        } while (true);

        return $productsArray;
    }

    public static function getItemsOzon($body = 0): object|false|null
    {
        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v4/product/info/attributes', $body);

        if(!$response->ok()) {
            return false;
        }

        return $response->object();
    }

    public static function getNotFoundSkus($allItems): array
    {
        $notFoundSkus = [];
        foreach ($allItems as $item) {
            $skuz = $item['skus'][0];

            if (!Sku::query()->where('sku', $skuz)->first()) {
                $notFoundSkus[] = $item;
            }
        }

        return $notFoundSkus;
    }

    public static function getAllNewOrders(): object|false|null
    {
        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders(['Authorization' => self::getWbApiKey()])
            ->get('https://marketplace-api.wildberries.ru/api/v3/orders/new');

        if(!$response->ok()) {
            return false;
        }

        return $response->object();
    }

    public static function uploadingNewProducts(): array
    {
        Log::channel('marketplace_api')->info('Начало загрузки.');

        $newOrders = MarketplaceApiService::getAllNewOrders()->orders;

        $arrayNotFoundSkus = [];
        $errors = [];

        foreach ($newOrders as $order) {
            try {
                DB::beginTransaction();

                $sku = Sku::query()->where('sku', $order->skus[0])->first();

                //  проверить что такой sku есть в системе
                if (!$sku) {
                    $arrayNotFoundSkus[$order->id] = $order->skus[0];

                    DB::rollBack();
                    continue;
                }

                // проверить есть ли такой заказ уже в системе
                if (MarketplaceOrder::query()->where('order_id', $order->id)->first()) {

                    DB::rollBack();
                    continue;
                }

                // добавить заказ в систему
                $marketplaceOrder = MarketplaceOrder::query()->create([
                    'order_id' => $order->id,
                    'marketplace_id' => 2,
                    'fulfillment_type' => $order->deliveryType,
                    'status' => 0,
                    'created_at' => Carbon::parse($order->createdAt)->setTimezone('Europe/Moscow')
                ]);

                // добавить материалы из заказа в систему
                foreach ($order->skus as $skus) {
                    $movementData['marketplace_order_id'] = $marketplaceOrder->id;
                    $movementData['marketplace_item_id'] = $sku->item_id;
                    $movementData['quantity'] = 1;
                    $movementData['price'] = 0;
                    $movementData['created_at'] = Carbon::parse($order->createdAt)->setTimezone('Europe/Moscow');

                    MarketplaceOrderItem::query()->create($movementData);
                }

                Log::channel('marketplace_api')->info('    Заказ №' . $order->id . ' добавлен в систему.');

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();

                // Собираем ошибки в массив
                $errors[$order->id] = [
                    'message' => $e->getMessage(),
                ];

                Log::channel('marketplace_api')
                    ->error('    Ошибка при загрузке заказа №' . $order->id . ': ' . $e->getMessage());
            }
        }

        Log::channel('marketplace_api')->info('Заказы загружены.');

        return [
            'not_found_skus' => $arrayNotFoundSkus,
            'errors' => $errors,
        ];
    }

    private static function getWbApiKey()
    {
        return Setting::query()->where('name', 'api_key_wb')->first()->value;
    }

    private static function getOzonApiKey()
    {
        return Setting::query()->where('name', 'api_key_ozon')->first()->value;
    }

    private static function getOzonSellerId()
    {
        return Setting::query()->where('name', 'seller_id_ozon')->first()->value;
    }
}
