<?php

namespace App\Services;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Sku;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketplaceApiService
{
    public static function getItems($cursor = 0): object|false|null
    {
        $response = Http::accept('application/json')
        ->withOptions(['verify' => false])
        ->withHeaders(['Authorization' => Config::get('marketplaces.wb_api_key')])
        ->post('https://content-api.wildberries.ru/content/v2/get/cards/list', $cursor);

        if(!$response->ok()) {
            return false;
        }

        return $response->object();
    }

    public static function getAllItems(): array
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
            $items = MarketplaceApiService::getItems($cursor);

            if(!$items) {
                return $productsArray;
            }

            foreach ($items->cards as $product) {
                $array = [
                    'imtID' => $product->imtID,
                    'nmID' => $product->nmID,
                    'title' => $product->title,
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
            ->withHeaders(['Authorization' => Config::get('marketplaces.wb_api_key')])
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
                    continue;
                }

                // проверить есть ли такой заказ уже в системе
                if (MarketplaceOrder::query()->where('order_id', $order->id)->first()) {
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
}
