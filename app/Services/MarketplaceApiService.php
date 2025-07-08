<?php

namespace App\Services;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Models\MovementMaterial;
use App\Models\Order;
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

    public static function uploadingCancelledProducts(): array
    {
        Log::channel('marketplace_api')->info('    Загрузка отмененных заказов...');

        $cancelledProductsWbNewStatus = self::getCancelledProductsWB('new');
        $resultWb1 = self::deleteCancelledProductsWb($cancelledProductsWbNewStatus);

        $cancelledProductsWbInWorkStatus = self::getCancelledProductsWB('in_work');
        $resultWb2 = self::changeToFBOCancelledProductsWb($cancelledProductsWbInWorkStatus);

        $cancelledProductsOzon = self::getCancelledProductsOZON();
        $resultOzon = self::checkCancelledProductsOzon($cancelledProductsOzon);

        Log::channel('marketplace_api')->info('    Загрузка отмененных заказов завершена.');

        return array_merge($resultWb1, $resultWb2, $resultOzon);
    }

    private static function getCancelledProductsWB($status): array
    {
        if ($status == 'new') {
            $orders = self::getNewStatusOrdersWb();
        } else {
            $orders = self::getInWorkStatusOrdersWb();
        }

        $unifiedOrders = [];

        if($orders != []) {
            $body = [
                "orders" => $orders
            ];

            $response = Http::accept('application/json')
                ->withOptions(['verify' => false])
                ->withHeaders(['Authorization' => self::getWbApiKey()])
                ->post('https://marketplace-api.wildberries.ru/api/v3/orders/status', $body);

            if(!$response->ok()) {
                Log::channel('marketplace_api')->info('ВНИМАНИЕ! Ошибка получения отмененных заказов из Wb');
                Log::channel('marketplace_api')->info($body);
                Log::channel('marketplace_api')->info(json_encode($response->object(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                return [];
            }

            $orders = $response->object()->orders;

            foreach ($orders as $order) {
                if ($order->wbStatus == "declined_by_client"){
                    $unifiedOrders[] = [
                        'id' => $order->id,
                        'marketplace_id' => '2',
                        'status' => $order->wbStatus
                    ];
                }
            }
        }

        return json_decode(json_encode($unifiedOrders));
    }

    private static function getNewStatusOrdersWb(): array
    {
        return MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
            ->where('marketplace_orders.marketplace_id', 2)
            ->where('marketplace_orders.fulfillment_type', 'FBS')
            ->where('marketplace_order_items.status', 0)
            ->pluck('marketplace_orders.order_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

    private static function getInWorkStatusOrdersWb(): array
    {
        return MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
            ->where('marketplace_orders.marketplace_id', 2)
            ->where('marketplace_orders.fulfillment_type', 'FBS')
            ->where('marketplace_order_items.status', 4)
            ->pluck('marketplace_orders.order_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

    private static function getCancelledProductsOZON(): array
    {
        $since = Carbon::now()->subDays(3)->format('Y-m-d\TH:i:s\Z'); // 3 дня назад
        $to = Carbon::now()->format('Y-m-d\TH:i:s\Z'); // сегодня

        $body = [
            "dir" => "ASC",
            "limit" => 1000,
            "offset" => 0,
            "filter" => [
                "since" => $since,
                "to" => $to,
                "status" => "cancelled",
            ],
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v3/posting/fbs/list', $body);


        if(!$response->ok()) {
            Log::channel('marketplace_api')->info('ВНИМАНИЕ! Ошибка получения отмененных заказов из Ozon');
            return [];
        }

        $orders = $response->object()->result->postings;

        $unifiedOrders = [];
        foreach ($orders as $order) {
            $unifiedOrders[] = [
                'id' => $order->posting_number,
                'marketplace_id' => '1',
            ];
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
//
//                    $result = match ($sku->marketplace_id) {
//                        1 => self::collectOrderOzon($order->id, $skus->sku),
//                        2 => self::collectOrderWb($order->id, $skus->sku),
//                        default => false,
//                    };
//
//                    if($result && $sku->marketplace_id == 1) {
//                        Log::channel('marketplace_api')->info('    Заказ №' . $order->id . ' успешно собран');
//                    } else {
//                        Log::channel('marketplace_api')->info('    Заказ №' . $order->id . ' НЕ собран');
//                        DB::rollBack();
//                        continue 2;
//                    }
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
            if ($response->object()->message === 'POSTING_ALREADY_SHIPPED') {
                Log::channel('marketplace_api')->info('    Заказа №'.$orderId .' уже ранее был отправлен в сборку.');
                return true;
            }

            Log::channel('marketplace_api')->error('    Ошибка при отправке заказа №'.$orderId);
            Log::channel('marketplace_api')->error('    Запрос:'.json_encode($body));
            Log::channel('marketplace_api')->error('    Ответ'.$response->body());
            return false;
        }

        return true;
    }

    public static function collectOrderWb($orderId): bool
    {
        // Получаем открытую поставку для WB (если ее нет, то сначала создаем новую).
        $supplyId = self::getWbSupplyId();

        // Добавляем сборочное задание для WB в эту поставку.
        $response = Http::withOptions(['verify' => false])
            ->withHeaders(['Authorization' => self::getWbApiKey()])
            ->patch('https://marketplace-api.wildberries.ru/api/v3/supplies/'.$supplyId.'/orders/'.$orderId);

        if($response->noContent()) {
            return true;
        }

        return false;
    }

    private static function hasOrderInSystem($id): bool
    {
        return MarketplaceOrder::query()->where('order_id', $id)->exists();
    }

    private static function hasSkuInSystem($sku): ?Sku
    {
        return Sku::query()->where('sku', $sku)->first();
    }

    private static function getWbSupplyId(): string
    {
        // Проверяем есть ли открытая поставка WB.
        $suppliesList = self::getAllSuppliesWb();
        foreach ($suppliesList as $supply) {
            if (!$supply['done']) {
                return $supply['id'];
            }
        }

        // Если нет - то создаем ее и возвращаем ее номер
        $newSupply = self::createSupplyWb();
        return $newSupply->id;
    }

    private static function getAllSuppliesWb(): array
    {
        $next = 0;
        $suppliesList = [];

        do {
            $response = self::getSuppliesWb($next);

            if(!$response || empty($response->supplies) || $response->next == 0) {
                return $suppliesList;
            }

            foreach ($response->supplies as $supply) {
                $array = [
                    'id' => $supply->id,
                    'done' => $supply->done,
                ];

                $suppliesList[] = $array;
            }

            if (isset($response->next)) {
                $next = $response->next;
            } else {
                break;
            }
        } while (true);

        return $suppliesList;
    }

    private static function getSuppliesWb($next = 0): object|false|null
    {
        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders(['Authorization' => self::getWbApiKey()])
            ->withQueryParameters(
                [
                    'limit' => 1000,
                    'next' => $next,
                ]
            )
            ->get('https://marketplace-api.wildberries.ru/api/v3/supplies');

        if(!$response->ok()) {
            return false;
        }

        return $response->object();
    }

    private static function createSupplyWb(): object|false|null
    {
        $body = [
            "name" => "Поставка от " . date('d.m.Y H:i'),
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders(['Authorization' => self::getWbApiKey()])
            ->post('https://marketplace-api.wildberries.ru/api/v3/supplies', $body);

        if(!$response->created()) {
            Log::channel('marketplace_api')
                ->error('    Не удалось создать новую поставку WB: ', [
                    'code' => $response->object()->code,
                    'message' => $response->object()->message,
                ]);
            return false;
        }

        return $response->object();
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

        $marketplaceOrder = MarketplaceOrder::query()
            ->where('order_id', $orderId)
            ->first();

        $marketplaceOrder->barcode = $response->object()->stickers[0]->barcode;
        $marketplaceOrder->part_b = $response->object()->stickers[0]->partB;
        $marketplaceOrder->save();

        $decodedData = base64_decode($response->object()->stickers[0]->file);

        $tempImagePath = sys_get_temp_dir() . '/image.png';
        file_put_contents($tempImagePath, $decodedData);

        $pdf = PDF::loadView('pdf.wb_sticker', ['imagePath' => $tempImagePath]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('barcode.pdf');
    }

    public static function getBarcodeOzonFBO(MarketplaceOrder $order): \Illuminate\Http\Response
    {
        $fullName = $order->items[0]->seamstress->name;

        $parts = explode(' ', $fullName);
        if (count($parts) >= 1) {
            $surname = $parts[0];
            $initials = '';

            if (isset($parts[1])) {
                $initials .= mb_substr($parts[1], 0, 1) . '.';
            }

            if (isset($parts[2])) {
                $initials .= mb_substr($parts[2], 0, 1) . '.';
            }

            $seamstressName = $surname . ' ' . $initials;
        } else {
            $seamstressName = $fullName;
        }

        $sku = Sku::query()
            ->where('item_id', $order->items[0]->item->id)
            ->where('marketplace_id', $order->marketplace_id)
            ->first();

        $barcode = ($order->marketplace_id == 1) ? 'OZN'.$sku->sku : $sku->sku;

        $pdf = PDF::loadView('pdf.fbo_ozon_sticker' , [
            'barcode' => $barcode,
            'item' => $order->items[0]->item,
            'seamstressName' => $seamstressName
        ]);

        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('barcode.pdf');
    }

    private static function checkCancelledProductsOzon(array $cancelledProductsOzon): array
    {
        $resultArray = [];

        foreach ($cancelledProductsOzon as $product) {
            $order = MarketplaceOrder::query()
                ->where('order_id', $product->id)
                ->where('fulfillment_type', 'FBS')
                ->first();

            if ($order) {
                $item = $order->items->first();

                switch ($item->status) {
                    case 0:

                        Log::channel('marketplace_api')->info('    Заказа №'.$order->order_id .' удален.');

                        $resultArray[] = [
                            'order_id' => $order->order_id,
                            'status' => 'удален',
                        ];

                        self::deleteAllOrderItemsMovementsAndOrdersMovements($order->id);

                        $order->delete();

                        break;
                    case 4:

                        Log::channel('marketplace_api')->info('    Заказа №'.$order->order_id .' изменен на FBO.');

                        $resultArray[] = [
                            'order_id' => $order->order_id,
                            'status' => 'изменен на FBO',
                        ];
                        $order->fulfillment_type = 'FBO';
                        $order->save();
                        break;
                }
            }
        }

        return $resultArray;
    }

    private static function deleteAllOrderItemsMovementsAndOrdersMovements($order_id): void
    {
        MarketplaceOrderItem::query()
            ->where('marketplace_order_id', $order_id)
            ->delete();

        $orderMovements = Order::query()
            ->where('marketplace_order_id', $order_id)
            ->get();

        foreach ($orderMovements as $orderMovement) {
            MovementMaterial::query()
                ->where('order_id', $orderMovement->id)
                ->delete();

            $orderMovement->delete();
        }
    }

    private static function deleteCancelledProductsWb(array $cancelledProductsWbNewStatus): array
    {
        $resultArray = [];

        foreach ($cancelledProductsWbNewStatus as $product) {

            Log::channel('marketplace_api')->info('    Заказа №'.$product->id .' удален.');

            $resultArray[] = [
                'order_id' => $product->id,
                'status' => 'удален',
            ];

            $order = MarketplaceOrder::query()
                ->where('order_id', $product->id)
                ->first();

            self::deleteAllOrderItemsMovementsAndOrdersMovements($order->id);

            $order->delete();
        }

        return $resultArray;
    }

    private static function changeToFBOCancelledProductsWb(array $cancelledProductsWbInWorkStatus): array
    {
        $resultArray = [];

        foreach ($cancelledProductsWbInWorkStatus as $product) {

            Log::channel('marketplace_api')->info('    Заказа №'.$product->id .' изменен на FBO.');

            $resultArray[] = [
                'order_id' => $product->id,
                'status' => 'изменен на FBO',
            ];

            $order = MarketplaceOrder::query()
                ->where('order_id', $product->id)
                ->first();

            $order->fulfillment_type = 'FBO';
            $order->save();
        }

        return $resultArray;
    }

    public static function getOzonPostingNumberByBarcode($barcode): array|string
    {
        $body = [
            "barcode" => $barcode,
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v2/posting/fbs/get-by-barcode', $body);

        if(!$response->ok()) {
            Log::channel('marketplace_api')
                ->info('ВНИМАНИЕ! Ошибка получения номера заказа из Ozon по штихкоду товара');
            return [];
        }

        $posting_number = $response->object()->result->posting_number;

        return json_decode(json_encode($posting_number));
    }

    public static function ozonSupply(MarketplaceSupply $marketplace_supply): bool
    {
        $newSupply = self::createSupplyOzon($marketplace_supply);
        if (empty($newSupply)) {
            return false;
        }

        $marketplace_supply->supply_id = $newSupply->id;
        $marketplace_supply->save();

        if(!empty(self::addOrdersToSupplyOzon($marketplace_supply))) {
            return false;
        }

        if(!self::sendForDeliveryOzon($marketplace_supply)) {
            return false;
        }

        return true;
    }

    public static function wbSupply(MarketplaceSupply $marketplace_supply): bool
    {
        $newSupply = self::createSupplyWb();
        if (empty($newSupply)) {
            return false;
        }

        $marketplace_supply->supply_id = $newSupply->id;
        $marketplace_supply->save();

        if(!empty(self::addOrdersToSupplyWb($marketplace_supply))) {
            return false;
        }

        if(!self::sendForDeliveryWB($marketplace_supply)) {
            return false;
        }

        return true;
    }

    private static function addOrdersToSupplyWb(MarketplaceSupply $marketplace_supply): array
    {
        $allOrders = MarketplaceOrder::query()
            ->where('supply_id', $marketplace_supply->id)
            ->get();

        $notAddedOrders = [];
        foreach ($allOrders as $order) {
            $url = 'https://marketplace-api.wildberries.ru/api/v3/supplies/'.$marketplace_supply->supply_id.'/orders/'.$order->order_id;
            $response = Http::accept('application/json')
                ->withOptions(['verify' => false])
                ->withHeaders(['Authorization' => self::getWbApiKey()])
                ->patch($url);

            if(!$response->noContent()) {
                Log::channel('marketplace_api')
                    ->error('    Заказа №'.$order->order_id.' не добавлен в поставку '. $marketplace_supply->id, [
                        'code' => $response->object()->code,
                        'message' => $response->object()->message,
                    ]);
               $notAddedOrders[] = $order->order_id;
            }
        }

        return $notAddedOrders;
    }

    private static function sendForDeliveryWB(MarketplaceSupply $marketplace_supply): bool
    {
        $url = 'https://marketplace-api.wildberries.ru/api/v3/supplies/'.$marketplace_supply->supply_id.'/deliver';

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders(['Authorization' => self::getWbApiKey()])
            ->post($url);

        if(!$response->noContent()) {
            Log::channel('marketplace_api')
                ->error('    Не удалось передать поставку '.  $marketplace_supply->id.' в доставку WB.', [
                    'code' => $response->object()->code,
                    'message' => $response->object()->message,
                ]);
            return false;
        }

        return true;
    }

    private static function createSupplyOzon(MarketplaceSupply $marketplace_supply)
    {
        $body = [
            "delivery_method_id" => $marketplace_supply->warehouse_id,
            "departure_date" => $marketplace_supply->warehouse_id,
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v1/carriage/create', $body);

        if(!$response->ok()) {
            Log::channel('marketplace_api')
                ->error('Не удалось создать новую поставку Ozon: ', [
                    'code' => $response->object()->code,
                    'message' => $response->object()->message,
                ]);
            return false;
        }

        return json_decode(json_encode($response->object()->carriage_id));
    }

    private static function addOrdersToSupplyOzon(MarketplaceSupply $marketplace_supply): array
    {
        $allOrders = MarketplaceOrder::query()
            ->where('supply_id', $marketplace_supply->id)
            ->get()
            ->toArray();

        $notAddedOrders = [];

            $body = [
                "carriage_id" => $marketplace_supply->supply_id,
                "posting_number" => $allOrders,
            ];

            $response = Http::accept('application/json')
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Client-Id' => self::getOzonSellerId(),
                    'Api-Key' => self::getOzonApiKey(),
                ])
                ->post('https://api-seller.ozon.ru/v1/carriage/set-postings', $body);

            if(!$response->ok()) {
                Log::channel('marketplace_api')
                    ->error('Ошибка при добавлении заказов в поставку OZON '. $marketplace_supply->id, [
                        'code' => $response->object()->code,
                        'message' => $response->object()->message,
                    ]);
                $notAddedOrders = $allOrders;
            }

        return $notAddedOrders;
    }

    private static function sendForDeliveryOzon(MarketplaceSupply $marketplace_supply): bool
    {
        $body = [
            "carriage_id" => $marketplace_supply->supply_id,
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v1/carriage/approve', $body);

        if(!$response->ok()) {
            Log::channel('marketplace_api')
                ->error('    Не удалось передать поставку '.  $marketplace_supply->id.' в доставку Ozon.', [
                    'code' => $response->object()->code,
                    'message' => $response->object()->message,
                ]);
            return false;
        }

        return true;
    }

    public static function checkStatusSupplyOzon(MarketplaceSupply $marketplace_supply): bool
    {
        $body = [
            "id" => $marketplace_supply->supply_id,
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v2/posting/fbs/digital/act/check-status', $body);

        if(!$response->ok()) {
            return false;
        }

        if($response->object()->status != 'FORMED'){
            return false;
        }

        return true;
    }

    public static function getDocsSupplyOzon(MarketplaceSupply $marketplace_supply)
    {
        $body = [
            "id" => $marketplace_supply->supply_id,
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v2/posting/fbs/act/get-pdf', $body);

        if (!$response->ok()) {
            return response()->json(['error' => 'Не удалось получить документы от Ozon'], 500);
        }

        $contentType = $response->object()->content_type ?? 'application/pdf';
        $fileName = $response->object()->file_name ?? 'document.pdf';
        $fileContent = $response->object()->file_content;

        $binaryContent = base64_decode($fileContent);

        return response($binaryContent)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Content-Length', strlen($binaryContent));
    }

    public static function getBarcodeSupplyOzon(MarketplaceSupply $marketplace_supply)
    {
        $isFormed = MarketplaceApiService::checkStatusSupplyOzon($marketplace_supply);
        if (!$isFormed){
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Документы еще не сформированы.');
        }

        $body = [
            "id" => $marketplace_supply->supply_id,
        ];

        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ])
            ->post('https://api-seller.ozon.ru/v2/posting/fbs/act/get-barcode', $body);

        if (!$response->ok()) {
            Log::channel('marketplace_api')->info('Ответ OZON ', [
                'code' => $response->object()->code,
                'message' => $response->object()->message,
            ]);
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось получить штрихкод поставки от Ozon');
        }

        $contentType = $response->object()->content_type ?? 'image/png';
        $fileName = $response->object()->file_name ?? 'barcode.png';
        $fileContent = $response->object()->file_content;

        $binaryContent = base64_decode($fileContent);

        return response($binaryContent)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Content-Length', strlen($binaryContent));
    }

    public static function getBarcodeSupplyWB(MarketplaceSupply $marketplace_supply)
    {
        $url = 'https://marketplace-api.wildberries.ru/api/v3/supplies/'.$marketplace_supply->supply_id.'/barcode?type=png';
        $response = Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders(['Authorization' => self::getWbApiKey()])
            ->get($url);

        if (!$response->ok()) {
            Log::channel('marketplace_api')->info('Ответ WB ', [
                'code' => $response->object()->code,
                'message' => $response->object()->message,
            ]);

            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось получить штрихкод поставки от WB');
        }

        $decodedData = base64_decode($response->object()->file);

        $tempImagePath = sys_get_temp_dir() . '/image.png';
        file_put_contents($tempImagePath, $decodedData);

        $pdf = PDF::loadView('pdf.wb_sticker', ['imagePath' => $tempImagePath]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('barcode.pdf');
    }
}
