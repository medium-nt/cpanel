<?php

namespace App\Services;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Setting;
use App\Models\Sku;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MarketplaceApiService
{
    public static function getItemsWb($body = 0): object|false|null
    {
        $response = Http::accept('application/json')
        ->withOptions(['verify' => false])
        ->withHeaders(['Authorization' => self::getWbApiKey()])
        ->post('https://content-api.wildberries.ru/content/v2/get/cards/list', $body);

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
            $items = self::getItemsWb($cursor);

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
            $items = self::getItemsOzon($body);

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

    public static function getAllNewOrdersWb(): array|object
    {
        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders(['Authorization' => self::getWbApiKey()])
            ->get('https://marketplace-api.wildberries.ru/api/v3/orders/new');

        if(!$response->ok()) {
            Log::channel('marketplace_api')->info('ВНИМАНИЕ! Ошибка получения новых заказов из Wb');
            return [];
        }

        $orders = $response->object()->orders;

        $unifiedOrders = [];
        foreach ($orders as $order) {
            $array = [
                'id' => $order->id,
                'skus' => [],
                'marketplace_id' => '2',
                'order_created' => $order->createdAt,
            ];

            foreach ($order->skus as $sku) {
                $array['skus'][] = [
                    'sku' => $sku,
                    'quantity' => 1
                ];
            }

            $unifiedOrders[] = $array;
        }

        return json_decode(json_encode($unifiedOrders));
    }

    public static function getAllNewOrdersOzon(): array|object
    {
        $cutoffFrom = Carbon::now()->subDays(7)->startOfDay()->format('Y-m-d\TH:i:s\Z'); // 7 дней назад
        $cutoffTo = Carbon::now()->addDays(14)->endOfDay()->format('Y-m-d\TH:i:s\Z'); // 14 дней вперед

        $body = [
            "dir" => "ASC",
            "limit" => 1000,
            "offset" => 0,
            "filter" => [
                "cutoff_from" => $cutoffFrom,
                "cutoff_to" => $cutoffTo,
                "status" => "awaiting_packaging",
            ],
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list', $body);

        if(!$response->ok()) {
            Log::channel('marketplace_api')->info('ВНИМАНИЕ! Ошибка получения новых заказов из Ozon');
            return [];
        }

        $orders = $response->object()->result->postings;

        $unifiedOrders = [];
        foreach ($orders as $order) {
            $array = [
                'id' => $order->posting_number,
                'skus' => [],
                'marketplace_id' => '1',
                'order_created' => $order->in_process_at,
            ];

            foreach ($order->products as $item) {
                $array['skus'][] = [
                    'sku' => $item->sku,
                    'quantity' => $item->quantity
                ];
            }

            $unifiedOrders[] = $array;
        }

        return json_decode(json_encode($unifiedOrders));
    }

    public static function uploadingNewProducts(): array
    {
        Log::channel('marketplace_api')->info('Начало загрузки новых заказов...');

        $newOrdersOzon = self::getAllNewOrdersOzon();
        $newOrdersOzonWithOneQuantity = self::splittingOrdersWithMoreThanOneQuantity($newOrdersOzon);

        $newOrdersWb = self::getAllNewOrdersWb();

        $newOrders = array_merge($newOrdersWb, $newOrdersOzonWithOneQuantity);

        $arrayNotFoundSkus = [];
        $errors = [];

        foreach ($newOrders as $order) {
            try {
                if (self::hasOrderInSystem($order->id)) {
                    continue;
                }

                foreach ($order->skus as $skus) {
                    if (!self::hasSkuInSystem($skus->sku)) {
                        $arrayNotFoundSkus[$order->id][] = $skus->sku;
                    }
                }
                if (isset($arrayNotFoundSkus[$order->id])) {
                    continue;
                }

                DB::beginTransaction();

                $marketplaceOrder = MarketplaceOrder::query()->create([
                    'order_id' => $order->id,
                    'marketplace_id' => $order->marketplace_id,
                    'fulfillment_type' => 'FBS',
                    'status' => 0,
                    'created_at' => Carbon::parse($order->order_created)->setTimezone('Europe/Moscow')
                ]);

                foreach ($order->skus as $skus) {
                    $sku = self::hasSkuInSystem($skus->sku);

                    $orderItem['marketplace_order_id'] = $marketplaceOrder->id;
                    $orderItem['marketplace_item_id'] = $sku->item_id;
                    $orderItem['quantity'] = 1;
                    $orderItem['price'] = 0;
                    $orderItem['created_at'] = Carbon::parse($order->order_created)->setTimezone('Europe/Moscow');

                    MarketplaceOrderItem::query()->create($orderItem);
                }

                Log::channel('marketplace_api')->info('    Заказ №' . $order->id . ' добавлен в систему.');

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();

                $errors[$order->id] = [
                    'message' => $e->getMessage(),
                ];

                Log::channel('marketplace_api')
                    ->error('    Ошибка при загрузке заказа №' . $order->id . ': ' . $e->getMessage());
            }
        }

        Log::channel('marketplace_api')->info('Конец загрузки новых заказов.');

        return [
            'not_found_skus' => $arrayNotFoundSkus,
            'errors' => $errors,
        ];
    }

    private static function splittingOrdersWithMoreThanOneQuantity($orders): array
    {
        $ordersWithOneQuantity = [];

        if (!empty($orders)) {
            foreach ($orders as $order) {
                $splitResult = null;

                if (count($order->skus) > 1) {
                    $splitResult = self::splittingOrder($order);
                } else {
                    foreach ($order->skus as $product) {
                        if ($product->quantity > 1) {
                            $splitResult = self::splittingOrder($order);
                            break;
                        }
                    }
                }

                if ($splitResult !== null) {
                    if ($splitResult) {
                        Log::channel('marketplace_api')->info('Разбит заказ №'.$order->id);
                    } else {
                        Log::channel('marketplace_api')->error('Ошибка при разбивке заказа №'.$order->id);
                    }
                } else {
                    $ordersWithOneQuantity[] = $order;
                }
            }
        }
        return $ordersWithOneQuantity;
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

    public static function splittingOrder($order): bool
    {
        $postings = [];

        foreach ($order->skus as $product) {
            for ($i = 0; $i < $product->quantity; $i++) {
                $postings[] = [
                    "products" => [[
                        "product_id" => $product->sku,
                        "quantity" => 1,
                    ]],
                ];
            }
        }

        $body = [
            "posting_number" => $order->id,
            "postings" => $postings,
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v1/posting/fbs/split', $body);

        if(!$response->ok()) {
            return false;
        }

        return true;
    }

    public static function collectOrderOzon($orderId, $product): bool
    {
        $body = [
            "packages" => [
                [
                    "products" => [
                        [
                            "product_id" => $product,
                            "quantity" => 1
                        ]
                    ]
                ]
            ],
            "posting_number" => $orderId,
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v4/posting/fbs/ship', $body);

        if(!$response->ok()) {
            Log::channel('marketplace_api')->error('    Ошибка при отправке заказа №'.$orderId);
            Log::channel('marketplace_api')->error('    Запрос:'.json_encode($body));
            Log::channel('marketplace_api')->error('    Ответ'.$response->body());
            return false;
        }

        return true;
    }

    private static function hasOrderInSystem($id): bool
    {
        return MarketplaceOrder::query()->where('order_id', $id)->exists();
    }

    private static function hasSkuInSystem($sku): ?Sku
    {
        return Sku::query()->where('sku', $sku)->first();
    }

    public static function getBarcodeOzon(mixed $orderId): object|false|null
    {
        $body = [
            "posting_number" => [
                $orderId
            ]
        ];

        $response = Http::accept('application/pdf')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v2/posting/fbs/package-label', $body);

        if (!$response->successful()) {
            echo "Ошибка получения стикера";
            exit;
        }

        if ($response->header('Content-Type') !== 'application/pdf') {
            echo "Получен стикер неверного формата";
            exit;
        }

        return response($response->body(), Response::HTTP_OK)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="barcode.pdf"');
    }

    public static function getBarcodeWb(int $orderId): object|false|null
    {
        $body = [
            'orders' => [
                $orderId,
            ],
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders(['Authorization' => self::getWbApiKey()])
            ->withQueryParameters( [
                'type' => 'png',
                'width' => 58,
                'height' => 40,
            ])
            ->post('https://marketplace-api.wildberries.ru/api/v3/orders/stickers', $body);

        if(!$response->ok()) {
            return false;
        }

        if (empty($response->object()->stickers)) {
            echo "Ошибка получения стикера";
            exit;
        }

        $decodedData = base64_decode($response->object()->stickers[0]->file);

        $tempImagePath = sys_get_temp_dir() . '/image.png';
        file_put_contents($tempImagePath, $decodedData);

        $pdf = PDF::loadView('pdf.wb_sticker', ['imagePath' => $tempImagePath]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('barcode.pdf');
    }

}
