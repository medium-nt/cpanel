<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Models\MarketplaceWarehouse;
use App\Services\StickerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WbApiService
{
    use MakesApiRequests;

    public static function getItems($body = 0): object|false|null
    {
        $response = self::wbRequest()
            ->post('https://content-api.wildberries.ru/content/v2/get/cards/list', $body);

        if (! $response->ok()) {
            return false;
        }

        return $response->object();
    }

    public static function getAllItems(): array
    {
        $productsArray = [];

        $limit = 100;
        $cursor = [
            'settings' => [
                'cursor' => [
                    'limit' => $limit,
                ],
                'filter' => [
                    'withPhoto' => -1,
                ],
            ],
        ];

        do {
            $items = self::getItems($cursor);

            if (! $items) {
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
                $cursor['settings']['cursor']['updatedAt'] = $items->cursor->updatedAt;
                $cursor['settings']['cursor']['nmID'] = $items->cursor->nmID;
            } else {
                break;
            }
        } while (true);

        return $productsArray;
    }

    public static function getAllNewOrders(): array|object
    {
        try {
            $response = self::wbRequest()
                ->get('https://marketplace-api.wildberries.ru/api/v3/orders/new');

            if (! $response->ok()) {
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
                    'is_b2b' => (bool) ($order->options?->isB2B),
                ];

                foreach ($order->skus as $sku) {
                    $array['skus'][] = [
                        'sku' => $sku,
                        'quantity' => 1,
                    ];
                }

                $unifiedOrders[] = $array;
            }

            return json_decode(json_encode($unifiedOrders));
        } catch (Throwable $e) {
            Log::channel('marketplace_api')
                ->error('ВНИМАНИЕ! Ошибка получения новых заказов из WB: '.$e->getMessage());

            return [];
        }
    }

    public static function collectOrder(int $orderId): bool
    {
        // Получаем открытую поставку для WB (если ее нет, то сначала создаем новую).
        $supplyId = self::getWbSupplyId();

        if ($supplyId === null) {
            Log::channel('marketplace_api')
                ->error('Не удалось получить открытую поставку WB для добавления в нее заказа.');

            return false;
        }

        try {
            // Добавляем сборочное задание для WB в эту поставку.
            $body = [
                'orders' => [(int) $orderId],
            ];

            $response = Http::withOptions(['verify' => false])
                ->withHeaders(['Authorization' => self::getWbApiKey()])
                ->patch('https://marketplace-api.wildberries.ru/api/marketplace/v3/supplies/'.$supplyId.'/orders', $body);

            if ($response->noContent()) {
                return true;
            }

            Log::channel('marketplace_api')->error(
                'Ошибка добавления в поставку WB заказа '.$orderId,
                [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]
            );
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка добавления в открытую поставку WB заказа '.$orderId.': '.$e->getMessage()
            );
        }

        return false;
    }

    public static function getItemBySku($sku): ?object
    {
        $body = [
            'settings' => [
                'cursor' => [
                    'limit' => 1,
                ],
                'filter' => [
                    'withPhoto' => -1,
                    'textSearch' => (string) $sku,
                ],
            ],
        ];

        $response = self::wbRequest()
            ->post('https://content-api.wildberries.ru/content/v2/get/cards/list', $body);

        if (! $response->ok()) {
            return null;
        }

        return $response->object()?->cards[0] ?? null;
    }

    public static function supply(MarketplaceSupply $marketplace_supply): bool
    {
        $newSupply = self::createSupply();
        if (empty($newSupply)) {
            Log::channel('marketplace_api')
                ->error('Не удалось создать поставку WB.');

            return false;
        }

        $marketplace_supply->supply_id = $newSupply->id;
        $marketplace_supply->save();

        Log::channel('marketplace_api')
            ->notice('Поставка '.$newSupply->id.' создана WB.');

        sleep(1);

        if (! empty(self::addOrdersToSupply($marketplace_supply))) {
            return false;
        }

        if (! self::sendForDelivery($marketplace_supply)) {
            return false;
        }

        Log::channel('marketplace_api')
            ->notice('Поставка '.$marketplace_supply->id.' успешно передана доставку WB.');

        return true;
    }

    public static function getBarcodeSupply(MarketplaceSupply $marketplace_supply)
    {
        $url = 'https://marketplace-api.wildberries.ru/api/v3/supplies/'.$marketplace_supply->supply_id.'/barcode?type=png';

        try {
            $response = self::wbRequest()
                ->get($url);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error('Не удалось получить штрихкод поставки от WB: ', [
                    'code' => $response->object()->code,
                    'message' => $response->object()->message,
                ]);

                return redirect()
                    ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                    ->with('error', 'Не удалось получить штрихкод поставки от WB');
            }

            $decodedData = base64_decode($response->object()->file);

            $tempImagePath = sys_get_temp_dir().'/image.png';
            file_put_contents($tempImagePath, $decodedData);

            $pdf = PDF::loadView('pdf.wb_sticker', ['imagePath' => $tempImagePath]);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('barcode.pdf');
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Не удалось получить штрихкод поставки от WB '.$marketplace_supply->id.' : '.$e->getMessage()
            );

            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось получить штрихкод поставки от WB');
        }
    }

    public static function updateStatusOrderBySupply(MarketplaceSupply $marketplace_supply): bool
    {
        $orders = MarketplaceOrder::query()
            ->where('supply_id', $marketplace_supply->id)
            ->pluck('order_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $hasError = false;

        try {
            $body = [
                'orders' => $orders,
            ];

            $response = self::wbRequest()
                ->post('https://marketplace-api.wildberries.ru/api/v3/orders/status', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error('Не удалось получить новые статусы заказов по WB', [
                    'code' => $response->object()->code,
                    'message' => $response->object()->message,
                ]);

                $hasError = true;
            }

            $orders = $response->object()->orders;

            $updated = [];
            foreach ($orders as $order) {
                MarketplaceOrder::query()
                    ->where('order_id', (string) $order->id)
                    ->update([
                        'marketplace_status' => $order->supplierStatus,
                    ]);

                $updated[(string) $order->id] = $order->supplierStatus;
            }

            if (! empty($updated)) {
                $summary = collect($updated)->map(fn ($v, $k) => '#'.$k.'→'.$v)->implode(', ');

                Log::channel('orders')
                    ->notice('Поставка #'.$marketplace_supply->id.' (WB): обновлён marketplace_status у '.
                        count($updated).' заказ(ов): '.$summary);
            }
        } catch (Throwable $e) {
            Log::channel('marketplace_api')
                ->error('Не удалось получить новые статусы заказов по поставке WB '.$marketplace_supply->id.' : '.$e->getMessage());

            $hasError = true;
        }

        return ! $hasError;
    }

    public static function syncWarehouses(): int
    {
        try {
            $response = self::wbRequest()
                ->get('https://supplies-api.wildberries.ru/api/v1/warehouses');

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'ВНИМАНИЕ! Ошибка получения складов из WB',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return 0;
            }

            $warehouses = $response->json() ?? [];
            $added = 0;
            $apiNames = [];

            foreach ($warehouses as $warehouse) {
                $warehouseName = $warehouse['name'] ?? '';

                if (empty($warehouseName)) {
                    continue;
                }

                $apiNames[] = $warehouseName;

                $created = MarketplaceWarehouse::query()->firstOrCreate(
                    [
                        'name' => $warehouseName,
                        'marketplace_id' => 2,
                    ],
                    [
                        'cluster' => '',
                    ]
                );

                if ($created->wasRecentlyCreated) {
                    $added++;
                }
            }

            $deleted = MarketplaceWarehouse::query()
                ->where('marketplace_id', 2)
                ->whereNotIn('name', $apiNames)
                ->delete();

            Log::channel('marketplace_api')
                ->info("Синхронизация складов WB завершена. Добавлено: {$added}, удалено: {$deleted}");

            return $added;
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при синхронизации складов WB: '.$e->getMessage()
            );

            return 0;
        }
    }

    public static function getFboSupplies(): array
    {
        try {
            $response = self::wbRequest()
                ->post('https://supplies-api.wildberries.ru/api/v1/supplies', [
                    'statusIDs' => [1, 2, 3, 4],
                ]);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'Ошибка получения FBO-поставок из WB',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return [];
            }

            return $response->json() ?? [];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении FBO-поставок WB: '.$e->getMessage()
            );

            return [];
        }
    }

    public static function getFboSupplyDetail(int $supplyId): array
    {
        try {
            $response = self::wbRequest()
                ->get("https://supplies-api.wildberries.ru/api/v1/supplies/{$supplyId}");

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    "Ошибка получения деталей FBO-поставки #{$supplyId} из WB",
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return [];
            }

            return $response->json() ?? [];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                "Ошибка при получении деталей FBO-поставки WB #{$supplyId}: ".$e->getMessage()
            );

            return [];
        }
    }

    public static function getFboSupplyGoods(int $supplyId): array
    {
        try {
            $response = self::wbRequest()
                ->get("https://supplies-api.wildberries.ru/api/v1/supplies/{$supplyId}/goods");

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    "Ошибка получения товаров FBO-поставки #{$supplyId} из WB",
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return [];
            }

            return $response->json() ?? [];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                "Ошибка при получении товаров FBO-поставки WB #{$supplyId}: ".$e->getMessage()
            );

            return [];
        }
    }

    /**
     * Получает стикер для WB-заказа и возвращает его как PDF-поток.
     */
    public static function getBarcode(int $orderId): object|false|null
    {
        $body = [
            'orders' => [
                $orderId,
            ],
        ];

        try {
            $response = self::wbRequest()
                ->withQueryParameters([
                    'type' => 'png',
                    'width' => 58,
                    'height' => 40,
                ])
                ->post('https://marketplace-api.wildberries.ru/api/v3/orders/stickers', $body);

            if (! $response->ok()) {
                return false;
            }

            if (empty($response->object()->stickers)) {
                echo 'Ошибка получения стикера';
                exit;
            }

            $marketplaceOrder = MarketplaceOrder::query()
                ->where('order_id', $orderId)
                ->first();

            $marketplaceOrder->barcode = $response->object()->stickers[0]->barcode;
            $marketplaceOrder->part_b = $response->object()->stickers[0]->partB;
            $marketplaceOrder->save();

            $decodedData = base64_decode($response->object()->stickers[0]->file);

            $tempImagePath = sys_get_temp_dir().'/image.png';
            file_put_contents($tempImagePath, $decodedData);

            $pdf = PDF::loadView('pdf.wb_sticker', ['imagePath' => $tempImagePath]);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->stream('barcode.pdf');
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка получения стикера WB '.$orderId.': '.$e->getMessage()
            );
            echo 'Ошибка получения стикера. Попробуйте повторить позже.';
            exit;
        }
    }

    /**
     * Генерирует PDF с лентой стикеров для WB FBO-заказов.
     */
    public static function getBarcodeFBO(Collection $orders): \Illuminate\Http\Response
    {
        $stickers = $orders->map(function (MarketplaceOrder $order) {
            $item = $order->items->first()->item;
            $sku = $item->sku->where('marketplace_id', $order->marketplace_id)->first()->sku;
            $barcode = $sku;

            $productSticker = \App\Models\ProductSticker::query()
                ->where('title', $item->title)
                ->first();

            return [
                'item' => $item,
                'barcode' => $barcode,
                'order' => $order,
                'fontSizeCluster' => StickerService::resolveFontSizeCluster($order->cluster, 'pdf.fbo_wb_sticker'),
                'seamstressId' => $order->items[0]->seamstress?->id,
                'cutterId' => $order->items[0]->cutter?->id,
                'article' => self::getItemBySku($sku)->nmID ?? '',
                'color' => $productSticker->color ?? '',
                'country' => $productSticker->country ?? '',
            ];
        })->all();

        $pdf = PDF::loadView('pdf.fbo_wb_sticker', [
            'stickers' => $stickers,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('barcode.pdf');
    }

    private static function getWbSupplyId(): ?string
    {
        // Проверяем есть ли открытая поставка WB.
        $suppliesList = self::getAllSupplies();
        foreach ($suppliesList as $supply) {
            if (! $supply['done']) {
                return $supply['id'];
            }
        }

        // Если нет - то создаем ее и возвращаем ее номер
        $newSupply = self::createSupply();

        if (empty($newSupply)) {
            return null;
        }

        return (string) $newSupply->id;
    }

    private static function getAllSupplies(): array
    {
        $next = 0;
        $suppliesList = [];

        do {
            $response = self::getSupplies($next);

            if (! $response || empty($response->supplies) || $response->next == 0) {
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

    private static function getSupplies($next = 0): object|false|null
    {
        try {
            $response = self::wbRequest()
                ->withQueryParameters([
                    'limit' => 1000,
                    'next' => $next,
                ])->get('https://marketplace-api.wildberries.ru/api/v3/supplies');

            if (! $response->ok()) {
                return false;
            }

            return $response->object();
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении списка поставок WB: '.$e->getMessage()
            );

            return false;
        }
    }

    private static function createSupply(): ?object
    {
        $body = [
            'name' => 'Поставка от '.date('d.m.Y H:i'),
        ];

        try {
            $response = self::wbRequest()
                ->post('https://marketplace-api.wildberries.ru/api/v3/supplies', $body);

            if (! $response->created()) {
                Log::channel('marketplace_api')
                    ->error('Не удалось создать новую поставку WB: ', [
                        'code' => $response->object()->code,
                        'message' => $response->object()->message,
                    ]);

                return null;
            }

            return $response->object();
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Не удалось создать новую поставку WB: '.$e->getMessage()
            );

            return null;
        }
    }

    private static function addOrdersToSupply(MarketplaceSupply $marketplace_supply): array
    {
        $allOrders = MarketplaceOrder::query()
            ->where('supply_id', $marketplace_supply->id)
            ->get();

        $notAddedOrders = [];
        foreach ($allOrders as $order) {
            try {
                $url = 'https://marketplace-api.wildberries.ru/api/marketplace/v3/supplies/'
                    .$marketplace_supply->supply_id.'/orders';

                $body = [
                    'orders' => [(int) $order->order_id],
                ];

                $response = Http::accept('application/json')
                    ->withOptions(['verify' => false])
                    ->withHeaders(['Authorization' => self::getWbApiKey()])
                    ->patch($url, $body);

                if (! $response->noContent()) {
                    Log::channel('marketplace_api')
                        ->error('Заказа №'.$order->order_id.' не добавлен в поставку WB '.$marketplace_supply->supply_id.' (id '.$marketplace_supply->id.')',
                            [
                                'status' => $response->status(),
                                'body' => $response->body(),
                            ]);
                    $notAddedOrders[] = $order->order_id;
                }
            } catch (Throwable $e) {
                Log::channel('marketplace_api')->error(
                    'Заказа №'.$order->order_id.' не добавлен в поставку WB '.$marketplace_supply->supply_id.' (id '.$marketplace_supply->id.'): '.$e->getMessage()
                );
                $notAddedOrders[] = $order->order_id;
            }
        }

        return $notAddedOrders;
    }

    private static function sendForDelivery(MarketplaceSupply $marketplace_supply): bool
    {
        $url = 'https://marketplace-api.wildberries.ru/api/v3/supplies/'.$marketplace_supply->supply_id.'/deliver';

        try {
            $response = self::wbRequest()
                ->patch($url);

            if (! $response->noContent()) {
                Log::channel('marketplace_api')
                    ->error('Не удалось передать поставку '.$marketplace_supply->id.' в доставку WB.', [
                        'code' => $response->object()->code,
                        'message' => $response->object()->message,
                    ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Не удалось передать поставку '.$marketplace_supply->id.' в доставку WB: '.$e->getMessage()
            );

            return false;
        }
    }

    /**
     * Получает текущий supplierStatus заказа WB.
     */
    public static function getStatusOrder(MarketplaceOrder $order)
    {
        $body = [
            'orders' => [
                (int) $order->order_id,
            ],
        ];

        try {
            $response = self::wbRequest()
                ->post('https://marketplace-api.wildberries.ru/api/v3/orders/status', $body);

            if (! $response->ok()) {
                return null;
            }

            return $response->object()->orders[0]->supplierStatus;
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении статуса заказа WB '.$order->order_id.': '.$e->getMessage()
            );

            return null;
        }
    }

    /**
     * Получает причину возврата (wbStatus) заказа WB и маппит её в человекочитаемую строку.
     */
    public static function getReturnReason(MarketplaceOrderItem $marketplace_item): string
    {
        $body = [
            'orders' => [
                (int) $marketplace_item->marketplaceOrder->order_id,
            ],
        ];

        try {
            $response = self::wbRequest()
                ->post('https://marketplace-api.wildberries.ru/api/v3/orders/status', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')
                    ->error('ВНИМАНИЕ! Ошибка получения причины возврата из WB по заказу '
                        .$marketplace_item->marketplaceOrder->order_id.' Ответ:',
                        [$response->object()]);

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
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'ВНИМАНИЕ! Ошибка получения причины возврата из WB по заказу '.
                $marketplace_item->marketplaceOrder->order_id.': '.$e->getMessage()
            );

            return '---';
        }
    }
}
