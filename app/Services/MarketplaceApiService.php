<?php

namespace App\Services;

use App\Jobs\SendTelegramMessageJob;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Sku;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\RedirectResponse;
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
        $response = self::wbRequest()
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
        $response = self::ozonRequest()
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
        $response = self::wbRequest()
            ->get('https://marketplace-api.wildberries.ru/api/v3/orders/new');

        if(!$response->ok()) {
            Log::channel('marketplace_api')->error('ВНИМАНИЕ! Ошибка получения новых заказов из Wb');
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

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list', $body);

        if(!$response->ok()) {
            Log::channel('marketplace_api')->error('ВНИМАНИЕ! Ошибка получения новых заказов из Ozon');
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
        Log::channel('marketplace_api')->notice('    Загрузка отмененных заказов...');

        $cancelledProductsWbNewStatus = self::getCancelledProductsWB('new');
        $resultWb1 = self::deleteCancelledProductsWb($cancelledProductsWbNewStatus);

        $cancelledProductsWbInWorkStatus = self::getCancelledProductsWB('in_work');
        $resultWb2 = self::changeToFBOCancelledProductsWb($cancelledProductsWbInWorkStatus);

        $cancelledProductsWbAssemblyStatus = self::getCancelledProductsWB('in_assembly');
        $resultWb3 = self::deleteAssemblyCancelledProductsWb($cancelledProductsWbAssemblyStatus);

        $cancelledProductsOzon = self::getCancelledProductsOZON();
        $resultOzon = self::checkCancelledProductsOzon($cancelledProductsOzon);

        Log::channel('marketplace_api')->notice('    Загрузка отмененных заказов завершена.');

        return array_merge($resultWb1, $resultWb2, $resultWb3, $resultOzon);
    }

    private static function getCancelledProductsWB($status): array
    {
        switch ($status) {
            case 'new':
                $orders = self::getNewStatusOrdersWb();
                $statusName = '"новый"';
                break;
            case 'in_work':
                $orders = self::getInWorkStatusOrdersWb();
                $statusName = '"в работе", "на стикеровке", "в закрое" или "откроено"';
                break;
            case 'in_assembly':
                $orders = self::getInAssemblyStatusOrdersWb();
                $statusName = '"в сборке"';
                break;
            default:
                return [];
        }

        Log::channel('marketplace_api')
            ->info('Получены отмененные заказы (статус в системе: ' . $statusName . '):' . json_encode($orders));

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
                Log::channel('marketplace_api')->error('ВНИМАНИЕ! Ошибка получения отмененных заказов из Wb');
                Log::channel('marketplace_api')->error($body);
                Log::channel('marketplace_api')->error(json_encode($response->object(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
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
            ->whereIn('marketplace_order_items.status', [4, 5, 7, 8])
            ->pluck('marketplace_orders.order_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

    private static function getInAssemblyStatusOrdersWb(): array
    {
        return MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
            ->where('marketplace_orders.marketplace_id', 2)
            ->where('marketplace_orders.fulfillment_type', 'FBS')
            ->where('marketplace_order_items.status', 13)
            ->pluck('marketplace_orders.order_id')
            ->map(fn($id) => (int)$id)
            ->toArray();
    }

    private static function getCancelledProductsOZON(): array
    {
        $since = Carbon::now()->subDays(7)->format('Y-m-d\TH:i:s\Z'); // 7 дней назад
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

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v3/posting/fbs/list', $body);


        if(!$response->ok()) {
            Log::channel('marketplace_api')->error('ВНИМАНИЕ! Ошибка получения отмененных заказов из Ozon');
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
        Log::channel('marketplace_api')->notice('Начало загрузки новых заказов...');

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

                    if (MarketplaceOrderItemService::hasReadyItem($sku)) {
                        Log::channel('marketplace_api')
                            ->info('Товар под заказ №' . $order->id . ' уже имеется в системе.');

                        MarketplaceOrderItemService::reserveReadyItem($sku, $marketplaceOrder);

                        $marketplaceOrder->status = 13; // в сборке
                        $marketplaceOrder->save();

                        $text = 'Поступил заказ на подбор со склада товара: ' .
                            $sku->item->title . ' - ' . $sku->item->width . ' x ' . $sku->item->height;

                        Log::channel('marketplace_api')
                            ->notice('Отправляем сообщение в ТГ работающему кладовщику и админу: ' . $text);

                        TgService::sendMessage(config('telegram.admin_id'), $text);

                        foreach (UserService::getListStorekeepersWorkingToday() as $index => $tgId) {
                            SendTelegramMessageJob::dispatch($tgId, $text)
                                ->delay(now()->addSeconds($index + 1));
                        }
                    } else {
                        Log::channel('marketplace_api')
                            ->info('Заказ №' . $order->id . ' добавлен в систему.');

                        MarketplaceOrderItemService::createItem($sku, $marketplaceOrder);

                        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceOrder->marketplace_id);

                        $materialName = $marketplaceOrder->items->first()->item->title;

                        $text = 'Поступил новый заказ ' . $materialName . ' на ' . $marketplaceName;

                        Log::channel('marketplace_api')
                            ->notice('Отправляем сообщение в ТГ админу и работающим швеям: ' . $text);

                        TgService::sendMessage(config('telegram.admin_id'), $text);

                        foreach (UserService::getListSeamstressesWorkingToday() as $index => $tgId) {
                            SendTelegramMessageJob::dispatch($tgId, $text)
                                ->delay(now()->addSeconds($index + 1));
                        }
                    }
                }

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

        Log::channel('marketplace_api')->notice('Конец загрузки новых заказов.');

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

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v1/posting/fbs/split', $body);

        if(!$response->ok()) {
            return false;
        }

        return true;
    }

    public static function collectOrderOzon($orderId, $product): bool
    {
        if (!self::verifyOrFixExemplarStatus($orderId)) {
            return false;
        }

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

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v4/posting/fbs/ship', $body);

        if(!$response->ok()) {
            if ($response->object()->message === 'POSTING_ALREADY_SHIPPED') {
                Log::channel('marketplace_api')->error('    Заказа №'.$orderId .' уже ранее был отправлен в сборку.');
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
        $response = self::wbRequest()
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

        $response = self::wbRequest()
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

        $response = self::wbRequest()
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

        Log::channel('marketplace_api')
            ->info('Получены отмененные заказы:' . json_encode($cancelledProductsOzon));

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
                    case 5:
                    case 7:
                    case 8:
                        Log::channel('marketplace_api')->info('    Заказа №'.$order->order_id .' изменен на FBO.');

                        $resultArray[] = [
                            'order_id' => $order->order_id,
                            'status' => 'изменен на FBO',
                        ];
                        $order->fulfillment_type = 'FBO';
                        $order->save();
                        break;
                    case 13:
                        Log::channel('marketplace_api')
                            ->info('Клиент отменил заказ №' . $order->order_id . ' Пробуем его удалить из системы...');

                        if ($order->status != 13) {
                            break;
                        }

                        MarketplaceOrderItemService::restoreOrderFromHistory($order->items->first());

                        $hasItems = MarketplaceOrderItem::query()
                            ->where('marketplace_order_id', $order->id)
                            ->exists();

                        if ($hasItems) {
                            Log::channel('marketplace_api')
                                ->error('Внимание! Заказа №' . $order->order_id . ' НЕ удален. Найдены товары для этого заказа.');
                            break;
                        }

                        $resultArray[] = [
                            'order_id' => $order->order_id,
                            'status' => 'удален',
                        ];

                        $order->delete();
                        Log::channel('marketplace_api')->info('Заказа №' . $order->order_id . ' удален.');
                        break;
                }
            }
        }

        Log::channel('marketplace_api')
            ->info('Отмененные заказы обработаны:' . json_encode($resultArray));

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

    private static function deleteAssemblyCancelledProductsWb(array $cancelledProductsWbNewStatus): array
    {
        $resultArray = [];

        foreach ($cancelledProductsWbNewStatus as $product) {
            Log::channel('marketplace_api')
                ->info('Клиент отменил заказ №' . $product->id . ' Пробуем его удалить из системы...');

            $order = MarketplaceOrder::query()
                ->where('order_id', $product->id)
                ->first();

            MarketplaceOrderItemService::restoreOrderFromHistory($order->items->first());

            $hasItems = MarketplaceOrderItem::query()
                ->where('marketplace_order_id', $order->id)
                ->exists();

            if ($hasItems) {
                Log::channel('marketplace_api')
                    ->error('Внимание! Заказа №' . $order->order_id . ' НЕ удален. Найдены товары для этого заказа.');
                continue;
            }

            $resultArray[] = [
                'order_id' => $order->order_id,
                'status' => 'удален',
            ];

            $order->delete();
            Log::channel('marketplace_api')->info('Заказа №' . $order->order_id . ' удален.');
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

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v2/posting/fbs/get-by-barcode', $body);

        if(!$response->ok()) {
            Log::channel('marketplace_api')
                ->error('ВНИМАНИЕ! Ошибка получения номера заказа из Ozon по штихкоду товара');
            return '-';
        }

        $posting_number = $response->object()->result->posting_number;

        return json_decode(json_encode($posting_number));
    }

    public static function getOzonPostingNumberByReturnBarcode($barcode): array|string
    {
        $body = [
            "filter" => [
                "barcode" => $barcode,
            ],
            "limit" => 1,
        ];

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v1/returns/list', $body);

        if (!$response->ok() || empty($response->object()->returns)) {
            Log::channel('marketplace_api')
                ->error('ВНИМАНИЕ! Ошибка получения номера заказа из Ozon по штихкоду возврата');
            return '-';
        }

        $posting_number = $response->object()->returns[0]->posting_number;

        return json_decode(json_encode($posting_number));
    }

    public static function ozonSupply(MarketplaceSupply $marketplace_supply): bool
    {
        $newSupply = self::createSupplyOzon();
        if (empty($newSupply)) {
            return false;
        }

        $marketplace_supply->supply_id = $newSupply;
        $marketplace_supply->save();

        if(!empty(self::addOrdersToSupplyOzon($marketplace_supply))) {
            return false;
        }

        if(!self::sendForDeliveryOzon($marketplace_supply)) {
            return false;
        }

        Log::channel('marketplace_api')
            ->notice('    Поставка '.  $marketplace_supply->id.' успешно передана доставку OZON.');

        return true;
    }

    public static function wbSupply(MarketplaceSupply $marketplace_supply): bool
    {
        $newSupply = self::createSupplyWb();
        if (empty($newSupply)) {
            Log::channel('marketplace_api')
                ->error('    Не удалось создать поставку WB.');
            return false;
        }

        $marketplace_supply->supply_id = $newSupply->id;
        $marketplace_supply->save();

        Log::channel('marketplace_api')
            ->notice('    Поставка '.  $newSupply->id.' создана WB.');

        sleep(1);

        if(!empty(self::addOrdersToSupplyWb($marketplace_supply))) {
            return false;
        }

        if(!self::sendForDeliveryWB($marketplace_supply)) {
            return false;
        }

        Log::channel('marketplace_api')
            ->notice('    Поставка '.  $marketplace_supply->id.' успешно передана доставку WB.');

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
                    ->error('    Заказа №'.$order->order_id.' не добавлен в поставку WB '. $marketplace_supply->supply_id . ' (id '. $marketplace_supply->id . ')', [
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

        $response = self::wbRequest()
            ->patch($url);

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

    private static function createSupplyOzon()
    {
        $body = [
            "delivery_method_id" => 1020000849274000,
            "departure_date" => now()->toIso8601String(),
        ];

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v1/carriage/create', $body);

        if(!$response->ok()) {
            Log::channel('marketplace_api')
                ->error('Не удалось создать новую поставку Ozon: ', [
                    'code' => $response->object()->code,
                    'message' => $response->object()->message,
                ]);
            return false;
        }

        Log::channel('marketplace_api')
            ->info('Новая поставка Ozon создалась успешно с номером: ' . $response->object()->carriage_id);

        return json_decode(json_encode($response->object()->carriage_id));
    }

    private static function addOrdersToSupplyOzon(MarketplaceSupply $marketplace_supply): array
    {
        $allOrders = MarketplaceOrder::query()
            ->where('supply_id', $marketplace_supply->id)
            ->pluck('order_id')
            ->toArray();

        $notAddedOrders = [];

            $body = [
                "carriage_id" => $marketplace_supply->supply_id,
                "posting_numbers" => $allOrders,
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

        $response = self::ozonRequest()
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

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v2/posting/fbs/digital/act/check-status', $body);

        if(!$response->ok()) {
            Log::channel('marketplace_api')
                ->error('Ошибка при скачивании документов поставки OZON '. $marketplace_supply->id, [
                    'code' => $response->object()->code,
                    'message' => $response->object()->message,
                ]);
            return false;
        }

        if (!in_array($response->object()->status, ['FORMED', 'CONFIRMED', 'CONFIRMED_WITH_MISMATCH'])) {
            Log::channel('marketplace_api')
                ->error('Документы к поставке '. $marketplace_supply->id . ' еще не сформированы.', [
                    'id' => $response->object()->id,
                    'status' => $response->object()->status,
                ]);
            return false;
        }

        return true;
    }

    public static function getDocsSupplyOzon(MarketplaceSupply $marketplace_supply)
    {
        $body = [
            "id" => $marketplace_supply->supply_id,
            "doc_type" => "act_of_acceptance",
        ];

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v2/posting/fbs/digital/act/get-pdf', $body);

        if (!$response->ok()) {
            Log::channel('marketplace_api')
                ->error('Не удалось получить документы от Ozon к поставке '. $marketplace_supply->id, [
                    'code' => $response->object()->code,
                    'message' => $response->object()->message,
                ]);

            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось получить документы от Ozon.');
        }

        return response($response->body(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="act_'. $marketplace_supply->id .'.pdf"');
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

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v2/posting/fbs/act/get-barcode', $body);

        if (!$response->ok()) {
            Log::channel('marketplace_api')->error('Не удалось получить штрихкод поставки от Ozon', [
                'code' => $response->object()->code,
                'message' => $response->object()->message,
            ]);
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось получить штрихкод поставки от Ozon');
        }

        return response($response->body(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="act_'. $marketplace_supply->id .'.pdf"');
    }

    public static function getBarcodeSupplyWB(MarketplaceSupply $marketplace_supply)
    {
        $url = 'https://marketplace-api.wildberries.ru/api/v3/supplies/'.$marketplace_supply->supply_id.'/barcode?type=png';
        $response = self::wbRequest()
            ->get($url);

        if (!$response->ok()) {
            Log::channel('marketplace_api')->error('Ответ WB ', [
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

    public static function updateStatusOrderBySupplyWB(MarketplaceSupply $marketplace_supply): RedirectResponse
    {
        $orders = MarketplaceOrder::query()
            ->where('supply_id', $marketplace_supply->id)
            ->pluck('order_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $body = [
            "orders" => $orders
        ];

        $response = self::wbRequest()
            ->post('https://marketplace-api.wildberries.ru/api/v3/orders/status', $body);

        if(!$response->ok()) {
            Log::channel('marketplace_api')->error('Не удалось получить новые статусы заказов по WB', [
                'code' => $response->object()->code,
                'message' => $response->object()->message,
            ]);

            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось получить новые статусы заказов по WB');
        }

        $orders = $response->object()->orders;

        foreach ($orders as $order) {
            MarketplaceOrder::query()
                ->where('order_id', (string) $order->id)
                ->update([
                    'marketplace_status' => $order->supplierStatus,
                ]);
        }

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
            ->with('success', 'Статусы заказов по WB обновлены');
    }

    public static function updateStatusOrderBySupplyOzon(MarketplaceSupply $marketplace_supply): RedirectResponse
    {
        $orders = MarketplaceOrder::query()
            ->where('supply_id', $marketplace_supply->id)
            ->pluck('order_id')
            ->toArray();

        $hasError = false;

        foreach ($orders as $order) {
            $body = [
                "posting_number" => $order,
            ];

            $response = Http::accept('application/json')
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Client-Id' => self::getOzonSellerId(),
                    'Api-Key' => self::getOzonApiKey(),
                ])
                ->post('https://api-seller.ozon.ru/v3/posting/fbs/get', $body);

            if(!$response->ok()) {
                Log::channel('marketplace_api')
                    ->error('Ошибка обновления статуса заказа #'. $order .' в Ozon');
                $hasError = true;
                continue;
            }

            MarketplaceOrder::query()
                ->where('order_id', $order)
                ->update([
                    'marketplace_status' => $response->object()->result->status,
                ]);
        }

        if($hasError){
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось получить новые статусы для всех заказов Ozon');
        }

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
            ->with('success', 'Статусы заказов по Ozon обновлены');

    }

    public static function getStatusOrder(MarketplaceOrder $order)
    {
        return match ($order->marketplace_id){
            1 => self::getStatusOrderOzon($order),
            2 => self::getStatusOrderWB($order),
            default => null
        };
    }

    private static function getStatusOrderOzon(MarketplaceOrder $order)
    {
        $body = [
            "posting_number" => $order->order_id,
        ];

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v3/posting/fbs/get', $body);

        if(!$response->ok()) {
            return null;
        }

        return $response->object()->result->status;
    }

    private static function getStatusOrderWB(MarketplaceOrder $order)
    {
        $body = [
            "orders" => [
                (int) $order->order_id,
            ]
        ];

        $response = self::wbRequest()
            ->post('https://marketplace-api.wildberries.ru/api/v3/orders/status', $body);

        if(!$response->ok()) {
            return null;
        }

        return $response->object()->orders[0]->supplierStatus;
    }

    private static function verifyOrFixExemplarStatus($orderId): bool
    {
        $body = [
            "posting_number" => $orderId,
        ];

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v4/fbs/posting/product/exemplar/status', $body);

        if (!$response->ok()) {
            Log::channel('marketplace_api')
                ->error('Не удалось получить статус экземпляров заказа '. $orderId);
            return false;
        }

        if ($response->object()->status == 'ship_available') {
            return true;
        }

        $text = 'Статус экземпляров заказа '. $orderId . ' не соответствует "ship_available"!' .
            ' Статус: '. $response->object()->status .
            ' Пробуем передать что ГТД не обязательна...';

        Log::channel('marketplace_api')
            ->error($text);

        return self::markExemplarAsGtdAbsent($response);
    }

    private static function markExemplarAsGtdAbsent($resp): bool
    {
        $response = $resp->json();

        if (
            empty($response['products']) ||
            empty($response['products'][0]) ||
            empty($response['products'][0]['exemplars']) ||
            empty($response['products'][0]['exemplars'][0])
        ) {
            Log::channel('marketplace_api')->error('Нет данных о продукте или экземпляре', ['response' => $response]);
            return false;
        }

        $product = $response['products'][0];
        $exemplar = $product['exemplars'][0];

        $body = [
            "posting_number" => $response['posting_number'],
            "products" => [
                [
                    "product_id" => $product['product_id'],
                    "is_gtd_needed" => true,
                    "exemplars" => [
                        [
                            "exemplar_id" => $exemplar['exemplar_id'],
                            "is_gtd_absent" => true
                        ]
                    ]
                ]
            ]
        ];

        $apiResponse = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v5/fbs/posting/product/exemplar/set', $body);

        if(!$apiResponse->ok()) {
            Log::channel('marketplace_api')
                ->error('Не удалось установить "ГТД отсутствует" для заказа ' . $response['posting_number'], [
                    'body' => $body,
                    'response' => $apiResponse->object()
                ]);
            return false;
        }

        Log::channel('marketplace_api')
            ->info('Установили "ГТД отсутствует" для заказа ' . $response['posting_number']);

        return true;
    }

    private static function ozonRequest(): PendingRequest
    {
        return Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders([
                'Client-Id' => self::getOzonSellerId(),
                'Api-Key' => self::getOzonApiKey(),
            ]);
    }

    private static function wbRequest(): PendingRequest
    {
        return Http::accept('application/json')
            ->withOptions(['verify' => false])
            ->withHeaders(['Authorization' => self::getWbApiKey()]);
    }

    public static function getReturnReason(MarketplaceOrderItem $marketplace_item): string
    {
        return match ($marketplace_item->marketplaceOrder->marketplace_id) {
            1 => self::getReturnReasonOzon($marketplace_item),
            2 => self::getReturnReasonWB($marketplace_item),
            default => '---',
        };
    }

    private static function getReturnReasonOzon(MarketplaceOrderItem $marketplace_item): string
    {
        $body = [
            "filter" => [
                "posting_numbers" => [
                    $marketplace_item->marketplaceOrder->order_id
                ],
            ],
            "limit" => 1,
        ];

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v1/returns/list', $body);

        if (!$response->ok()) {
            Log::channel('marketplace_api')
                ->error('ВНИМАНИЕ! Ошибка получения причины возврата из Ozon по заказу '
                    . $marketplace_item->marketplaceOrder->order_id . ' Ответ:' . $response->object());
            return '---';
        }

        return $response->object()?->returns[0]->return_reason_name ?? '---';
    }

    private static function getReturnReasonWB(MarketplaceOrderItem $marketplace_item): string
    {
        $body = [
            "orders" => [
                (int)$marketplace_item->marketplaceOrder->order_id,
            ],
        ];

        $response = self::wbRequest()
            ->post('https://marketplace-api.wildberries.ru/api/v3/orders/status', $body);

        if (!$response->ok()) {
            Log::channel('marketplace_api')
                ->error('ВНИМАНИЕ! Ошибка получения причины возврата из WB по заказу '
                    . $marketplace_item->marketplaceOrder->order_id);

            return '---';
        }

        return match ($response->object()->orders[0]->wbStatus) {
            'waiting', 'sorted' => 'Задание в работе',
            'ready_for_pickup' => 'Прибыло на (ПВЗ)',
            'sold' => 'Заказ получен покупателем',
            'canceled_by_client' => 'Покупатель отменил заказ при получении',
            'declined_by_client' => 'Покупатель отменил заказ сразу после заказа',
            'canceled ' => 'Отмена сборочного задания',
            'defect' => 'Отмена заказа по причине брака',
            default => '---',
        };
    }
}
