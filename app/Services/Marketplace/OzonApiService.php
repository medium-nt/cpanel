<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Models\MarketplaceWarehouse;
use App\Services\StickerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OzonApiService
{
    use MakesApiRequests;

    public static function getItems($body = 0): object|false|null
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v4/product/info/attributes', $body);

            if (! $response->ok()) {
                return false;
            }

            return $response->object();
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'ВНИМАНИЕ! Ошибка получения всех товаров и sku из Ozon: '.$e->getMessage()
            );

            return false;
        }
    }

    public static function getAllItems(): array
    {
        $productsArray = [];
        $lastId = '';

        $limit = 100;
        $body = [
            'filter' => [
                'visibility' => 'ALL',
            ],
            'limit' => $limit,
            'sort_dir' => 'ASC',
            'last_id' => $lastId,
        ];

        do {
            $items = self::getItems($body);

            if (! $items) {
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
                $body['last_id'] = $items->last_id;
            } else {
                break;
            }
        } while (true);

        return $productsArray;
    }

    public static function getAllNewOrders(): array|object
    {
        $cutoffFrom = Carbon::now()->subDays(7)->startOfDay()->format('Y-m-d\TH:i:s\Z'); // 7 дней назад
        $cutoffTo = Carbon::now()->addDays(14)->endOfDay()->format('Y-m-d\TH:i:s\Z'); // 14 дней вперед

        $body = [
            'dir' => 'ASC',
            'limit' => 1000,
            'offset' => 0,
            'filter' => [
                'cutoff_from' => $cutoffFrom,
                'cutoff_to' => $cutoffTo,
                'status' => 'awaiting_packaging',
            ],
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list', $body);

            if (! $response->ok()) {
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
                    'is_b2b' => ($order->legal_info?->inn) || ($order->legal_info?->company_name),
                ];

                foreach ($order->products as $item) {
                    $array['skus'][] = [
                        'sku' => $item->sku,
                        'quantity' => $item->quantity,
                    ];
                }

                $unifiedOrders[] = $array;
            }

            return json_decode(json_encode($unifiedOrders));
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'ВНИМАНИЕ! Ошибка получения новых заказов из Ozon: '.$e->getMessage()
            );

            return [];
        }
    }

    public static function collectOrder($orderId, $product): bool
    {
        if (! self::verifyOrFixExemplarStatus($orderId)) {
            return false;
        }

        $body = [
            'packages' => [
                [
                    'products' => [
                        [
                            'product_id' => $product,
                            'quantity' => 1,
                        ],
                    ],
                ],
            ],
            'posting_number' => $orderId,
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v4/posting/fbs/ship', $body);

            if (! $response->ok()) {
                if ($response->object()->message === 'POSTING_ALREADY_SHIPPED') {
                    Log::channel('marketplace_api')->error('Заказа №'.$orderId.' уже ранее был отправлен в сборку.');

                    return true;
                }

                Log::channel('marketplace_api')->error('Ошибка при отправке заказа №'.$orderId);
                Log::channel('marketplace_api')->error('Запрос:'.json_encode($body));
                Log::channel('marketplace_api')->error('Ответ'.$response->body());

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при отправке в сборку заказа '.$orderId.': '.$e->getMessage()
            );

            return false;
        }
    }

    public static function getBarcodeBySku(?string $sku): ?string
    {
        if (empty($sku)) {
            return null;
        }

        $body = [
            'sku' => [$sku],
        ];

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v3/product/info/list', $body);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return $data['items'][0]['barcodes'][0] ?? null;
    }

    /**
     * Получает PDF-стикер для OZON FBS-заказа по номеру отправления.
     */
    public static function getBarcode(mixed $orderId): object|false|null
    {
        $body = [
            'posting_number' => [
                $orderId,
            ],
        ];

        try {
            $response = Http::accept('application/pdf')
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Client-Id' => self::getOzonSellerId(),
                    'Api-Key' => self::getOzonApiKey(),
                ])
                ->post('https://api-seller.ozon.ru/v2/posting/fbs/package-label', $body);

            if (! $response->successful()) {
                echo 'Ошибка получения стикера';
                exit;
            }

            if ($response->header('Content-Type') !== 'application/pdf') {
                echo 'Получен стикер неверного формата';
                exit;
            }

            return response($response->body(), Response::HTTP_OK)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="barcode.pdf"');

        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка получения стикера OZON '.$orderId.': '.$e->getMessage()
            );
            echo 'Ошибка получения стикера';
            exit;
        }
    }

    /**
     * Генерирует PDF с лентой стикеров для Ozon FBO-заказов.
     */
    public static function getBarcodeFBO(Collection $orders): \Illuminate\Http\Response
    {
        $stickers = $orders->map(function (MarketplaceOrder $order) {
            $item = $order->items->first()->item;
            $sku = $item->sku->where('marketplace_id', $order->marketplace_id)->first()->sku;
            $barcode = ($order->marketplace_id == 1) ? self::getBarcodeBySku($sku) : $sku;

            return [
                'barcode' => $barcode,
                'item' => $item,
                'order' => $order,
                'fontSizeCluster' => StickerService::resolveFontSizeCluster($order->cluster, 'pdf.fbo_ozon_sticker'),
                'seamstressId' => $order->items[0]->seamstress?->id,
                'cutterId' => $order->items[0]->cutter?->id,
            ];
        })->all();

        $pdf = PDF::loadView('pdf.fbo_ozon_sticker', [
            'stickers' => $stickers,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('barcode.pdf');
    }

    /**
     * Генерирует HTML-представление стикера для OZON FBO-заказа (для предпросмотра).
     */
    public static function getBarcodeFBOHtml(MarketplaceOrder $order): \Illuminate\View\View
    {
        $item = $order->items->first()->item;
        $sku = $item->sku->where('marketplace_id', $order->marketplace_id)->first()->sku;
        $barcode = ($order->marketplace_id == 1) ? self::getBarcodeBySku($sku) : $sku;

        return view('pdf.fbo_ozon_sticker_html', [
            'barcode' => $barcode,
            'item' => $item,
            'seamstressId' => $order->items[0]->seamstress->id,
        ]);
    }

    public static function getPostingNumberByBarcode($barcode): array|string
    {
        $body = [
            'barcode' => $barcode,
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v2/posting/fbs/get-by-barcode', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')
                    ->error('ВНИМАНИЕ! Ошибка получения номера заказа из Ozon по штихкоду товара:'.
                        json_encode(['code' => $response->status(), 'body' => $response->body()]));

                return '-';
            }

            $posting_number = $response->object()->result->posting_number;

            return json_decode(json_encode($posting_number));
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'ВНИМАНИЕ! Ошибка получения номера заказа из Ozon по штихкоду товара '.$barcode.': '.$e->getMessage()
            );

            return '-';
        }
    }

    /**
     * Ищем по стикеру в возвратах номер заказа.
     * Если не нашли, ищем по номеру стикера (сразу по номеру стикера нальзя - может быть пусто).
     */
    public static function getPostingNumberByReturnBarcode($barcode): array|string
    {
        $body = [
            'filter' => [
                'barcode' => $barcode,
            ],
            'limit' => 1,
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/returns/list', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')
                    ->error('ВНИМАНИЕ! Ошибка получения номера заказа из Ozon по штихкоду возврата');

                return '-';
            }

            if (empty($response->object()->returns)) {
                // сделать запрос по номеру стикера, а не по возвратам
                return self::getPostingNumberByBarcode($barcode);
            }

            $posting_number = $response->object()->returns[0]->posting_number;

            return json_decode(json_encode($posting_number));
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'ВНИМАНИЕ! Ошибка получения номера заказа из Ozon по штихкоду возврата '.$barcode.': '.$e->getMessage()
            );

            return '-';
        }
    }

    public static function supply(MarketplaceSupply $marketplace_supply): bool
    {
        $newSupply = self::createSupply();
        if (empty($newSupply)) {
            return false;
        }

        $marketplace_supply->supply_id = $newSupply;
        $marketplace_supply->save();

        if (! empty(self::addOrdersToSupply($marketplace_supply))) {
            return false;
        }

        if (! self::sendForDelivery($marketplace_supply)) {
            return false;
        }

        Log::channel('marketplace_api')
            ->notice('Поставка '.$marketplace_supply->id.' успешно передана доставку OZON.');

        return true;
    }

    public static function checkStatusSupply(MarketplaceSupply $marketplace_supply): bool
    {
        $body = [
            'id' => $marketplace_supply->supply_id,
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v2/posting/fbs/digital/act/check-status', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')
                    ->error('Ошибка при скачивании документов поставки OZON '.$marketplace_supply->id, [
                        'code' => $response->object()->code,
                        'message' => $response->object()->message,
                    ]);

                return false;
            }

            if (! in_array($response->object()->status, ['FORMED', 'CONFIRMED', 'CONFIRMED_WITH_MISMATCH'])) {
                Log::channel('marketplace_api')
                    ->error('Документы к поставке '.$marketplace_supply->id.' еще не сформированы.', [
                        'id' => $response->object()->id,
                        'status' => $response->object()->status,
                    ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при скачивании документов поставки OZON '.$marketplace_supply->id.' : '.$e->getMessage()
            );

            return false;
        }
    }

    public static function getDocsSupply(MarketplaceSupply $marketplace_supply)
    {
        $body = [
            'id' => $marketplace_supply->supply_id,
            'doc_type' => 'act_of_acceptance',
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v2/posting/fbs/digital/act/get-pdf', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')
                    ->error('Не удалось получить документы от Ozon к поставке '.$marketplace_supply->id, [
                        'code' => $response->object()->code,
                        'message' => $response->object()->message,
                    ]);

                return redirect()
                    ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                    ->with('error', 'Не удалось получить документы от Ozon.');
            }

            return response($response->body(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="act_'.$marketplace_supply->id.'.pdf"');
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Не удалось получить документы от Ozon к поставке '.$marketplace_supply->id.' : '.$e->getMessage()
            );

            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось получить документы от Ozon.');
        }
    }

    public static function getBarcodeSupply(MarketplaceSupply $marketplace_supply)
    {
        $isFormed = self::checkStatusSupply($marketplace_supply);
        if (! $isFormed) {
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Документы еще не сформированы.');
        }

        $body = [
            'id' => $marketplace_supply->supply_id,
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v2/posting/fbs/act/get-barcode', $body);

            if (! $response->ok()) {
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
                ->header('Content-Disposition', 'inline; filename="act_'.$marketplace_supply->id.'.pdf"');
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Не удалось получить штрихкод от Ozon по поставке '.$marketplace_supply->id.' : '.$e->getMessage()
            );

            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось получить штрихкод поставки от Ozon');
        }
    }

    public static function updateStatusOrderBySupply(MarketplaceSupply $marketplace_supply): bool
    {
        $orders = MarketplaceOrder::query()
            ->where('supply_id', $marketplace_supply->id)
            ->pluck('order_id')
            ->toArray();

        $hasError = false;

        $updated = [];

        foreach ($orders as $order) {
            $body = [
                'posting_number' => $order,
            ];

            try {
                $response = Http::accept('application/json')
                    ->withOptions(['verify' => false])
                    ->withHeaders([
                        'Client-Id' => self::getOzonSellerId(),
                        'Api-Key' => self::getOzonApiKey(),
                    ])
                    ->post('https://api-seller.ozon.ru/v3/posting/fbs/get', $body);

                if (! $response->ok()) {
                    Log::channel('marketplace_api')
                        ->error('Ошибка обновления статуса заказа #'.$order.' в Ozon');
                    $hasError = true;

                    continue;
                }

                MarketplaceOrder::query()
                    ->where('order_id', $order)
                    ->update([
                        'marketplace_status' => $response->object()->result->status,
                    ]);

                $updated[$order] = $response->object()->result->status;
            } catch (Throwable $e) {
                Log::channel('marketplace_api')
                    ->error('Ошибка обновления статуса заказа # '.$order.' : '.$e->getMessage());

                $hasError = true;

                continue;
            }
        }

        if (! empty($updated)) {
            $summary = collect($updated)->map(fn ($v, $k) => '#'.$k.'→'.$v)->implode(', ');

            Log::channel('orders')
                ->notice('Поставка #'.$marketplace_supply->id.' (Ozon): обновлён marketplace_status у '.
                    count($updated).' заказ(ов): '.$summary);
        }

        return ! $hasError;
    }

    /**
     * Проверяет статус экземпляров заказа. И если он не соответствует "ship_available", то пытаемся добавить ГТД.
     */
    private static function verifyOrFixExemplarStatus($orderId): bool
    {
        $body = [
            'posting_number' => $orderId,
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v5/fbs/posting/product/exemplar/status', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')
                    ->error('Не удалось получить статус экземпляров заказа '.$orderId,
                        [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]
                    );

                return false;
            }

            if ($response->object()->status == 'ship_available') {
                return true;
            }

            $text = 'Статус экземпляров заказа '.$orderId.' не соответствует "ship_available"!'.
                ' Статус: '.$response->object()->status."\n".
                ' Пробуем передать что ГТД не обязательна...';

            Log::channel('marketplace_api')
                ->error($text);

            return self::markExemplarAsGtdAbsent($response);
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при запросе статуса экземпляров заказа '.$orderId.': '.$e->getMessage()
            );

            return false;
        }
    }

    /**
     * Передаем что для данного заказа ГТД не обязательна
     */
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

        //        if (! self::setCountryIsoCode($response['posting_number'], $product['product_id'])) {
        //            return false;
        //        }

        if (! self::setGtdAbsent($response['posting_number'], $product['product_id'], $exemplar['exemplar_id'])) {
            return false;
        }

        return true;
    }

    /**
     * Пытаемся установить атрибут "Страна-изготовитель".
     */
    private static function setCountryIsoCode(string $postingNumber, string $productId): bool
    {
        $body = [
            'posting_number' => $postingNumber,
            'product_id' => $productId,
            'country_iso_code' => 'RU',
        ];

        $apiResponse = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v2/posting/fbs/product/country/set', $body);

        if (! $apiResponse->ok()) {
            Log::channel('marketplace_api')
                ->error('Не удалось установить "Страна-изготовитель" для заказа '.$postingNumber, [
                    'body' => $body,
                    'response' => $apiResponse->object(),
                ]);

            return false;
        }

        Log::channel('marketplace_api')
            ->info('Установлен "Страна-изготовитель" для заказа '.$postingNumber);

        return true;
    }

    /**
     * Пытаемся установить "ГТД отсутствует"
     */
    private static function setGtdAbsent(string $postingNumber, string $productId, string $exemplarId): bool
    {
        $body = [
            'posting_number' => $postingNumber,
            'products' => [
                [
                    'product_id' => $productId,
                    'exemplars' => [
                        [
                            'exemplar_id' => $exemplarId,
                            'is_gtd_absent' => true,
                        ],
                    ],
                ],
            ],
        ];

        $apiResponse = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v6/fbs/posting/product/exemplar/set', $body);

        if (! $apiResponse->ok()) {
            Log::channel('marketplace_api')
                ->error('Не удалось установить "ГТД отсутствует" для заказа '.$postingNumber, [
                    'body' => $body,
                    'response' => $apiResponse->object(),
                ]);

            return false;
        }

        Log::channel('marketplace_api')
            ->info('Установили "ГТД отсутствует" для заказа '.$postingNumber);

        return true;
    }

    public static function getReturnsGiveoutPng(): ?array
    {
        return Cache::remember('ozon_returns_giveout_png', 43200, function () {
            try {
                $responsePng = self::ozonRequest()
                    ->post('https://api-seller.ozon.ru/v1/return/giveout/get-png', null);

                $responseBarcode = self::ozonRequest()
                    ->post('https://api-seller.ozon.ru/v1/return/giveout/barcode', null);

                if (! $responsePng->ok() || ! $responseBarcode->ok()) {
                    Log::channel('marketplace_api')->error(
                        'ВНИМАНИЕ! Ошибка получения штрих-кода выдачи возвратов из Ozon',
                        [
                            'status_png' => $responsePng->status(),
                            'status_barcode' => $responseBarcode->status(),
                        ]
                    );

                    return null;
                }

                $dataPng = $responsePng->json();
                $dataBarcode = $responseBarcode->json();

                return [
                    'png' => $dataPng['png'] ?? null,
                    'barcode' => $dataBarcode['barcode'] ?? null,
                ];
            } catch (Throwable $e) {
                Log::channel('marketplace_api')->error(
                    'Ошибка получения штрих-кода выдачи возвратов из Ozon: '.$e->getMessage()
                );

                return null;
            }
        });
    }

    public static function resetReturnsGiveoutBarcode(): ?array
    {
        // Сбрасываем кеш
        Cache::forget('ozon_returns_giveout_png');

        try {
            $responsePng = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/return/giveout/barcode-reset', null);

            $responseBarcode = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/return/giveout/barcode', null);

            if (! $responsePng->ok() || ! $responseBarcode->ok()) {
                Log::channel('marketplace_api')->error(
                    'ВНИМАНИЕ! Ошибка сброса штрих-кода выдачи возвратов из Ozon',
                    [
                        'status_png' => $responsePng->status(),
                        'status_barcode' => $responseBarcode->status(),
                    ]
                );

                return null;
            }

            $dataPng = $responsePng->json();
            $dataBarcode = $responseBarcode->json();

            $result = [
                'png' => $dataPng['png'] ?? null,
                'barcode' => $dataBarcode['barcode'] ?? null,
            ];

            // Сохраняем в кеш
            if ($result['png'] || $result['barcode']) {
                Cache::put('ozon_returns_giveout_png', $result, 86400);
            }

            return $result;
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка сброса штрих-кода выдачи возвратов из Ozon: '.$e->getMessage()
            );

            return null;
        }
    }

    public static function getReturnsCompanyFbsInfo(): array
    {
        try {
            $body = [
                'filter' => (object) [],
                'pagination' => [
                    'limit' => 100,
                    'offset' => 0,
                ],
            ];

            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/returns/company/fbs/info', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'ВНИМАНИЕ! Ошибка получения списка возвратов из Ozon',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return [];
            }

            $data = $response->json();

            return $data['drop_off_points'] ?? [];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка получения списка возвратов из Ozon: '.$e->getMessage()
            );

            return [];
        }
    }

    public static function getReturnsGiveoutList(): array
    {
        try {
            $body = [
                'limit' => 100,
                'last_id' => 0,
            ];

            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/return/giveout/list', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'ВНИМАНИЕ! Ошибка получения списка активных выдач из Ozon',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return [];
            }

            $data = $response->json();

            return $data['giveouts'] ?? [];

        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка получения списка активных выдач из Ozon: '.$e->getMessage()
            );

            return [];
        }
    }

    public static function getReturnsGiveoutInfo(int $giveoutId): ?array
    {
        try {
            $body = [
                'giveout_id' => $giveoutId,
            ];

            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/return/giveout/info', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'ВНИМАНИЕ! Ошибка получения информации о выдаче из Ozon',
                    [
                        'giveout_id' => $giveoutId,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка получения информации о выдаче из Ozon: '.$e->getMessage()
            );

            return null;
        }
    }

    public static function getReturnsList(array $filter = [], int $limit = 100, $lastId = null): array
    {
        try {
            $body = [
                'filter' => $filter,
                'limit' => $limit,
            ];

            if ($lastId !== null) {
                $body['last_id'] = $lastId;
            }

            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/returns/list', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'ВНИМАНИЕ! Ошибка получения списка возвратов из Ozon',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return [
                    'returns' => [],
                    'has_next' => false,
                ];
            }

            $data = $response->json();

            return [
                'returns' => $data['returns'] ?? [],
                'has_next' => $data['has_next'] ?? false,
            ];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка получения списка возвратов из Ozon: '.$e->getMessage()
            );

            return [
                'returns' => [],
                'has_next' => false,
            ];
        }
    }

    /**
     * Загружает склады OZON из API и добавляет в БД те, которых еще нет.
     */
    public static function syncWarehouses(): int
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/cluster/list', [
                    'cluster_type' => 'CLUSTER_TYPE_OZON',
                ]);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'ВНИМАНИЕ! Ошибка получения кластеров/складов из Ozon',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return 0;
            }

            $clusters = $response->json('clusters') ?? [];
            $added = 0;
            $apiNames = [];

            foreach ($clusters as $cluster) {
                $clusterName = $cluster['name'] ?? '';
                $macrolocalId = $cluster['macrolocal_cluster_id'] ?? null;

                foreach ($cluster['logistic_clusters'] ?? [] as $logisticCluster) {
                    foreach ($logisticCluster['warehouses'] ?? [] as $warehouse) {
                        $warehouseName = $warehouse['name'] ?? '';

                        if (empty($warehouseName)) {
                            continue;
                        }

                        $apiNames[] = $warehouseName;

                        $record = MarketplaceWarehouse::query()->updateOrCreate(
                            [
                                'name' => $warehouseName,
                                'marketplace_id' => 1,
                            ],
                            [
                                'cluster' => $clusterName,
                                'warehouse_id' => $warehouse['warehouse_id'] ?? null,
                                'macrolocal_cluster_id' => $macrolocalId,
                            ]
                        );

                        if ($record->wasRecentlyCreated) {
                            $added++;
                        }
                    }
                }
            }

            $deleted = MarketplaceWarehouse::query()
                ->where('marketplace_id', 1)
                ->whereNotIn('name', $apiNames)
                ->delete();

            Log::channel('marketplace_api')
                ->info("Синхронизация складов OZON завершена. Добавлено: {$added}, удалено: {$deleted}");

            return $added;
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при синхронизации складов OZON: '.$e->getMessage()
            );

            return 0;
        }
    }

    /**
     * Получает список складов продавца FBO из OZON API.
     */
    public static function getSellerWarehouses(): array
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/warehouse/fbo/seller/list', new \stdClass);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'Ошибка получения складов продавца FBO из OZON',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]
                );

                return [];
            }

            return $response->json('warehouses') ?? [];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении складов продавца FBO OZON: '.$e->getMessage()
            );

            return [];
        }
    }

    /**
     * Получает информацию о статусе черновика поставки OZON.
     */
    public static function getDraftInfo(int $draftId): ?array
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v2/draft/create/info', [
                    'draft_id' => $draftId,
                ]);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'Ошибка получения статуса черновика OZON',
                    ['status' => $response->status(), 'body' => $response->body(), 'draft_id' => $draftId]
                );

                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении статуса черновика OZON: '.$e->getMessage()
            );

            return null;
        }
    }

    /**
     * Получает список доступных складов для черновика поставки OZON.
     */
    public static function getDraftWarehouses(int $draftId): array
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v2/draft/create/info', [
                    'draft_id' => $draftId,
                ]);

            if (! $response->ok()) {
                $message = $response->status() === 429
                    ? 'Слишком частый запрос к OZON. Подождите немного и попробуйте снова.'
                    : ($response->json('message') ?: $response->body());

                Log::channel('marketplace_api')->error(
                    'Ошибка получения складов черновика OZON',
                    ['status' => $response->status(), 'body' => $response->body(), 'draft_id' => $draftId]
                );

                return ['warehouses' => [], 'error' => $message];
            }

            $clusters = $response->json('clusters') ?? [];

            $warehouses = [];
            foreach ($clusters as $cluster) {
                foreach ($cluster['warehouses'] ?? [] as $warehouse) {
                    if (($warehouse['availability_status']['state'] ?? '') !== 'FULL_AVAILABLE') {
                        continue;
                    }

                    $warehouses[] = [
                        'bundle_id' => $warehouse['bundle_id'] ?? $warehouse['restricted_bundle_id'] ?? '',
                        'warehouse_id' => $warehouse['storage_warehouse']['warehouse_id'] ?? 0,
                        'name' => $warehouse['storage_warehouse']['name'] ?? '',
                        'address' => $warehouse['storage_warehouse']['address'] ?? '',
                        'total_rank' => $warehouse['total_rank'] ?? 0,
                        'macrolocal_cluster_id' => $cluster['macrolocal_cluster_id'] ?? 0,
                        'supply_type' => $cluster['supply_type'] ?? 'DIRECT',
                    ];
                }
            }

            usort($warehouses, fn ($a, $b) => $a['total_rank'] <=> $b['total_rank']);

            return ['warehouses' => $warehouses, 'error' => null];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении складов черновика OZON: '.$e->getMessage()
            );

            return ['warehouses' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Получает таймслоты для выбранного склада черновика OZON.
     */
    public static function getDraftTimeslots(int $draftId, string $supplyType, int $macrolocalClusterId, int $storageWarehouseId, string $dateFrom, string $dateTo): array
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v2/draft/timeslot/info', [
                    'draft_id' => $draftId,
                    'supply_type' => $supplyType,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'selected_cluster_warehouses' => [
                        [
                            'macrolocal_cluster_id' => $macrolocalClusterId,
                            'storage_warehouse_id' => $storageWarehouseId,
                        ],
                    ],
                ]);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'Ошибка получения таймслотов черновика OZON',
                    ['status' => $response->status(), 'body' => $response->body(), 'draft_id' => $draftId]
                );

                return [];
            }

            return $response->json('result.drop_off_warehouse_timeslots.days') ?? [];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении таймслотов черновика OZON: '.$e->getMessage()
            );

            return [];
        }
    }

    /**
     * Создаёт черновик прямой поставки FBO через OZON API.
     */
    public static function createDraftDirect(int $macrolocalClusterId, array $items): array
    {
        $body = [
            'cluster_info' => [
                'items' => $items,
                'macrolocal_cluster_id' => $macrolocalClusterId,
            ],
            'deletion_sku_mode' => 'PARTIAL',
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/draft/direct/create', $body);

            if (! $response->ok()) {
                $message = $response->json('message') ?: $response->body();

                Log::channel('marketplace_api')->error(
                    'Ошибка создания черновика прямой поставки OZON FBO',
                    ['status' => $response->status(), 'body' => $response->body(), 'request' => $body]
                );

                return ['draft_id' => null, 'error' => $message];
            }

            return ['draft_id' => $response->json('draft_id'), 'error' => null];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при создании черновика прямой поставки OZON FBO: '.$e->getMessage()
            );

            return ['draft_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Создаёт черновик кросс-докинг поставки FBO через OZON API.
     */
    public static function createDraftCrossdock(int $macrolocalClusterId, int $sellerWarehouseId, array $items): array
    {
        $body = [
            'cluster_info' => [
                'items' => $items,
                'macrolocal_cluster_id' => $macrolocalClusterId,
            ],
            'deletion_sku_mode' => 'PARTIAL',
            'delivery_info' => [
                'seller_warehouse_id' => $sellerWarehouseId,
                'type' => 'DROPOFF',
            ],
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/draft/crossdock/create', $body);

            if (! $response->ok()) {
                $message = $response->json('message') ?: $response->body();

                Log::channel('marketplace_api')->error(
                    'Ошибка создания черновика кросс-докинг поставки OZON FBO',
                    ['status' => $response->status(), 'body' => $response->body(), 'request' => $body]
                );

                return ['draft_id' => null, 'error' => $message];
            }

            return ['draft_id' => $response->json('draft_id'), 'error' => null];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при создании черновика кросс-докинг поставки OZON FBO: '.$e->getMessage()
            );

            return ['draft_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Создаёт заявку на поставку из черновика через OZON API.
     */
    public static function createSupplyFromDraft(int $draftId, int $macrolocalClusterId, int $storageWarehouseId, string $fromTime, string $toTime, string $supplyType): array
    {
        $body = [
            'draft_id' => $draftId,
            'selected_cluster_warehouses' => [
                [
                    'macrolocal_cluster_id' => $macrolocalClusterId,
                    'storage_warehouse_id' => $storageWarehouseId,
                ],
            ],
            'timeslot' => [
                'from_in_timezone' => $fromTime,
                'to_in_timezone' => $toTime,
            ],
            'supply_type' => $supplyType,
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v2/draft/supply/create', $body);

            if (! $response->ok()) {
                $message = $response->json('message') ?: $response->body();

                Log::channel('marketplace_api')->error(
                    'Ошибка создания заявки из черновика OZON FBO',
                    ['status' => $response->status(), 'body' => $response->body(), 'request' => $body]
                );

                return ['order_id' => null, 'error' => $message];
            }

            $errorReasons = $response->json('error_reasons') ?? [];

            if (! empty($errorReasons)) {
                Log::channel('marketplace_api')->error(
                    'OZON вернул ошибки при создании заявки',
                    ['error_reasons' => $errorReasons, 'draft_id' => $draftId]
                );

                return ['order_id' => null, 'error' => implode(', ', $errorReasons)];
            }

            return ['order_id' => $response->json('draft_id'), 'error' => null];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при создании заявки из черновика OZON FBO: '.$e->getMessage()
            );

            return ['order_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Получает статус создания заявки на поставку из черновика OZON.
     */
    public static function getSupplyCreateStatus(int $draftId): array
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v2/draft/supply/create/status', [
                    'draft_id' => $draftId,
                ]);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'Ошибка получения статуса создания заявки OZON',
                    ['status' => $response->status(), 'body' => $response->body(), 'draft_id' => $draftId]
                );

                return ['status' => 'UNSPECIFIED', 'order_id' => null, 'error_reasons' => []];
            }

            return [
                'status' => $response->json('status') ?? 'UNSPECIFIED',
                'order_id' => $response->json('order_id'),
                'error_reasons' => $response->json('error_reasons') ?? [],
            ];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении статуса создания заявки OZON: '.$e->getMessage()
            );

            return ['status' => 'UNSPECIFIED', 'order_id' => null, 'error_reasons' => []];
        }
    }

    /**
     * Отменяет заявку на поставку через OZON API.
     */
    public static function cancelSupply(int $orderId): array
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/supply-order/cancel', [
                    'order_id' => $orderId,
                ]);

            if (! $response->ok()) {
                $message = $response->json('message') ?: $response->body();

                Log::channel('marketplace_api')->error(
                    'Ошибка отмены заявки на поставку OZON',
                    ['status' => $response->status(), 'body' => $response->body(), 'order_id' => $orderId]
                );

                return ['operation_id' => null, 'error' => $message];
            }

            return ['operation_id' => $response->json('operation_id'), 'error' => null];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при отмене заявки на поставку OZON: '.$e->getMessage()
            );

            return ['operation_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Получает статус отмены заявки на поставку через OZON API.
     */
    public static function getCancelSupplyStatus(string $operationId): array
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/supply-order/cancel/status', [
                    'operation_id' => $operationId,
                ]);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'Ошибка получения статуса отмены заявки OZON',
                    ['status' => $response->status(), 'body' => $response->body(), 'operation_id' => $operationId]
                );

                return ['status' => 'UNSPECIFIED', 'error_reasons' => []];
            }

            return [
                'status' => $response->json('status') ?? 'UNSPECIFIED',
                'result' => $response->json('result'),
                'error_reasons' => $response->json('error_reasons') ?? [],
            ];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении статуса отмены заявки OZON: '.$e->getMessage()
            );

            return ['status' => 'UNSPECIFIED', 'error_reasons' => []];
        }
    }

    /**
     * Получает список заявок на поставку OZON в состоянии DATA_FILLING.
     *
     * @return array Массив order_ids или пустой массив при ошибке
     */
    public static function getSupplyOrderList(): array
    {
        try {
            $allOrderIds = [];
            $lastId = '';

            do {
                $body = [
                    'filter' => [
                        'states' => ['DATA_FILLING'],
                    ],
                    'limit' => 100,
                    'sort_by' => 'ORDER_CREATION',
                    'sort_dir' => 'DESC',
                ];

                if ($lastId !== '') {
                    $body['last_id'] = $lastId;
                }

                $response = self::ozonRequest()
                    ->post('https://api-seller.ozon.ru/v3/supply-order/list', $body);

                if (! $response->ok()) {
                    Log::channel('marketplace_api')->error(
                        'Ошибка получения списка заявок на поставку OZON',
                        ['status' => $response->status(), 'body' => $response->body()]
                    );

                    return $allOrderIds;
                }

                $data = $response->json();
                $orderIds = $data['order_ids'] ?? [];
                $allOrderIds = array_merge($allOrderIds, $orderIds);
                $lastId = $data['last_id'] ?? '';
            } while ($lastId !== '');

            return $allOrderIds;
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении списка заявок на поставку OZON: '.$e->getMessage()
            );

            return [];
        }
    }

    /**
     * Получает детали заявки на поставку OZON.
     *
     * @return array Массив с данными заявки или пустой массив при ошибке
     */
    public static function getSupplyOrderDetails(int $orderId): array
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/supply-order/details', [
                    'order_id' => $orderId,
                ]);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    "Ошибка получения деталей заявки на поставку OZON #{$orderId}",
                    ['status' => $response->status(), 'body' => $response->body()]
                );

                return [];
            }

            return $response->json() ?? [];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                "Ошибка при получении деталей заявки на поставку OZON #{$orderId}: ".$e->getMessage()
            );

            return [];
        }
    }

    /**
     * Получает товары бандлов заявки на поставку OZON с пагинацией.
     *
     * @param  array<string>  $bundleIds  Массив ID бандлов
     * @return array Плоский массив всех товаров бандла
     */
    public static function getSupplyOrderBundle(array $bundleIds): array
    {
        try {
            $allItems = [];
            $lastId = null;
            $maxPages = 50;
            $page = 0;

            do {
                $body = [
                    'bundle_ids' => $bundleIds,
                    'is_asc' => true,
                    'limit' => 100,
                    'sort_field' => 'NAME',
                ];

                if ($lastId !== null) {
                    $body['last_id'] = $lastId;
                }

                $response = self::ozonRequest()
                    ->post('https://api-seller.ozon.ru/v1/supply-order/bundle', $body);

                if (! $response->ok()) {
                    Log::channel('marketplace_api')->error(
                        'Ошибка получения товаров бандла заявки на поставку OZON',
                        ['status' => $response->status(), 'body' => $response->body(), 'bundle_ids' => $bundleIds]
                    );

                    return $allItems;
                }

                $items = $response->json('items') ?? [];
                $allItems = array_merge($allItems, $items);

                $hasNext = $response->json('has_next') ?? false;
                $lastId = $response->json('last_id');
                $page++;

                if ($page >= $maxPages) {
                    Log::channel('marketplace_api')->warning(
                        'Достигнут лимит страниц при получении товаров бандла OZON',
                        ['bundle_ids' => $bundleIds, 'pages' => $page]
                    );

                    break;
                }
            } while ($hasNext);

            return $allItems;
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении товаров бандла заявки на поставку OZON: '.$e->getMessage()
            );

            return [];
        }
    }

    /**
     * Создаёт грузоместо для короба OZON FBO-поставки.
     */
    public static function createCargo(array $payload): array
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/cargoes/create', $payload);

            if (! $response->ok()) {
                $message = $response->json('message') ?: $response->body();

                Log::channel('marketplace_api')->error(
                    'Ошибка создания грузоместа OZON',
                    ['status' => $response->status(), 'body' => $response->body()]
                );

                return ['operation_id' => null, 'error' => $message];
            }

            return ['operation_id' => $response->json('operation_id'), 'error' => null];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при создании грузоместа OZON: '.$e->getMessage()
            );

            return ['operation_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Получает информацию о результате создания грузоместа OZON.
     */
    public static function getCargoCreateInfo(string $operationId): array
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v2/cargoes/create/info', [
                    'operation_id' => $operationId,
                ]);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'Ошибка получения информации о грузоместе OZON',
                    ['status' => $response->status(), 'body' => $response->body(), 'operation_id' => $operationId]
                );

                return ['status' => 'ERROR', 'cargo_id' => null, 'error' => $response->body()];
            }

            $status = $response->json('status') ?? 'UNSPECIFIED';
            $cargoes = $response->json('result.cargoes') ?? [];
            $cargoId = $cargoes[0]['value']['cargo_id'] ?? null;

            return ['status' => $status, 'cargo_id' => $cargoId, 'error' => null];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении информации о грузоместе OZON: '.$e->getMessage()
            );

            return ['status' => 'ERROR', 'cargo_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Создаёт этикетку (стикер) для грузоместа OZON.
     */
    public static function createCargoLabel(string $supplyId, array $cargoIds): array
    {
        try {
            $cargoes = array_map(fn (int $cargoId) => ['cargo_id' => $cargoId], $cargoIds);

            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/cargoes-label/create', [
                    'supply_id' => $supplyId,
                    'cargoes' => $cargoes,
                ]);

            if (! $response->ok()) {
                $message = $response->json('message') ?: $response->body();

                Log::channel('marketplace_api')->error(
                    'Ошибка создания этикетки грузоместа OZON',
                    ['status' => $response->status(), 'body' => $response->body(), 'supply_id' => $supplyId]
                );

                return ['operation_id' => null, 'error' => $message];
            }

            return ['operation_id' => $response->json('operation_id'), 'error' => null];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при создании этикетки грузоместа OZON: '.$e->getMessage()
            );

            return ['operation_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Получает информацию об этикетке грузоместа OZON (file_url).
     */
    public static function getCargoLabel(string $operationId): array
    {
        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/cargoes-label/get', [
                    'operation_id' => $operationId,
                ]);

            if (! $response->ok()) {
                Log::channel('marketplace_api')->error(
                    'Ошибка получения этикетки грузоместа OZON',
                    ['status' => $response->status(), 'body' => $response->body(), 'operation_id' => $operationId]
                );

                return ['status' => 'ERROR', 'file_url' => null, 'error' => $response->body()];
            }

            $status = $response->json('status') ?? 'UNSPECIFIED';
            $fileUrl = $response->json('result.file_url') ?? null;

            return ['status' => $status, 'file_url' => $fileUrl, 'error' => null];
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при получении этикетки грузоместа OZON: '.$e->getMessage()
            );

            return ['status' => 'ERROR', 'file_url' => null, 'error' => $e->getMessage()];
        }
    }

    private static function createSupply()
    {
        $body = [
            'delivery_method_id' => 1020000849274000,
            'departure_date' => now()->toIso8601String(),
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/carriage/create', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')
                    ->error('Не удалось создать новую поставку Ozon: ', [
                        'code' => $response->object()->code,
                        'message' => $response->object()->message,
                    ]);

                return false;
            }

            Log::channel('marketplace_api')
                ->info('Новая поставка Ozon создалась успешно с номером: '.$response->object()->carriage_id);

            return json_decode(json_encode($response->object()->carriage_id));
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Не удалось создать новую поставку Ozon: '.$e->getMessage()
            );

            return false;
        }
    }

    private static function addOrdersToSupply(MarketplaceSupply $marketplace_supply): array
    {
        $allOrders = MarketplaceOrder::query()
            ->where('supply_id', $marketplace_supply->id)
            ->pluck('order_id')
            ->toArray();

        $notAddedOrders = [];

        try {
            $body = [
                'carriage_id' => $marketplace_supply->supply_id,
                'posting_numbers' => $allOrders,
            ];

            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/carriage/set-postings', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')
                    ->error('Ошибка при добавлении заказов в поставку OZON '.$marketplace_supply->id, [
                        'code' => $response->object()->code,
                        'message' => $response->object()->message,
                    ]);
                $notAddedOrders = $allOrders;
            }
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при добавлении заказов в поставку OZON '.$marketplace_supply->id.' : '.$e->getMessage()
            );
            $notAddedOrders = $allOrders;
        }

        return $notAddedOrders;
    }

    private static function sendForDelivery(MarketplaceSupply $marketplace_supply): bool
    {
        $body = [
            'carriage_id' => $marketplace_supply->supply_id,
        ];

        try {
            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/carriage/approve', $body);

            if (! $response->ok()) {
                Log::channel('marketplace_api')
                    ->error('Не удалось передать поставку '.$marketplace_supply->id.' в доставку Ozon.', [
                        'code' => $response->object()->code,
                        'message' => $response->object()->message,
                    ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Не удалось передать поставку '.$marketplace_supply->id.' в доставку Ozon: '.$e->getMessage()
            );

            return false;
        }
    }

    /**
     * Получает текущий статус заказа Ozon по posting_number.
     */
    public static function getStatusOrder(MarketplaceOrder $order)
    {
        $body = [
            'posting_number' => $order->order_id,
        ];

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v3/posting/fbs/get', $body);

        if (! $response->ok()) {
            return null;
        }

        return $response->object()->result->status;
    }

    /**
     * Получает причину возврата экземпляра заказа Ozon по posting_number.
     */
    public static function getReturnReason(MarketplaceOrderItem $marketplace_item): string
    {
        $body = [
            'filter' => [
                'posting_numbers' => [
                    $marketplace_item->marketplaceOrder->order_id,
                ],
            ],
            'limit' => 1,
        ];

        $response = self::ozonRequest()
            ->post('https://api-seller.ozon.ru/v1/returns/list', $body);

        if (! $response->ok()) {
            Log::channel('marketplace_api')
                ->error('ВНИМАНИЕ! Ошибка получения причины возврата из Ozon по заказу '
                    .$marketplace_item->marketplaceOrder->order_id.' Ответ:',
                    [$response->object()]);

            return '---';
        }

        return $response->object()?->returns[0]->return_reason_name ?? '---';
    }
}
