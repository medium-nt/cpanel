<?php

namespace App\Services;

use App\Models\Marketplace;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Sku;
use App\Services\Marketplace\MakesApiRequests;
use App\Services\Marketplace\OzonApiService;
use App\Services\Marketplace\WbApiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketplaceApiService
{
    use MakesApiRequests;

    public static function getItemsWb($body = 0): object|false|null
    {
        return WbApiService::getItems($body);
    }

    public static function getAllItemsWb(): array
    {
        return WbApiService::getAllItems();
    }

    public static function getItemsOzon($body = 0): object|false|null
    {
        return OzonApiService::getItems($body);
    }

    public static function getAllItemsOzon(): array
    {
        return OzonApiService::getAllItems();
    }

    public static function getNotFoundSkus($allItems): array
    {
        // TODO: N+1 — загрузить все SKU одним запросом и искать в коллекции вместо запроса в цикле
        $notFoundSkus = [];
        foreach ($allItems as $item) {
            $skuz = $item['skus'][0];

            if (! Sku::query()->where('sku', $skuz)->first()) {
                $notFoundSkus[] = $item;
            }
        }

        return $notFoundSkus;
    }

    public static function getAllNewOrdersWb(): array|object
    {
        return WbApiService::getAllNewOrders();
    }

    public static function getAllNewOrdersOzon(): array|object
    {
        return OzonApiService::getAllNewOrders();
    }

    public static function uploadingCancelledProducts(): array
    {
        Log::channel('marketplace_api')->notice('Загрузка отмененных заказов...');

        $cancelledProductsWbNewStatus = self::getCancelledProductsWB('new');
        $resultWb1 = self::deleteCancelledProductsWb($cancelledProductsWbNewStatus);

        $cancelledProductsWbInWorkStatus = self::getCancelledProductsWB('in_work');
        $resultWb2 = self::changeToFBOCancelledProductsWb($cancelledProductsWbInWorkStatus);

        $cancelledProductsWbAssemblyStatus = self::getCancelledProductsWB('in_assembly');
        $resultWb3 = self::deleteAssemblyCancelledProductsWb($cancelledProductsWbAssemblyStatus);

        $cancelledProductsOzon = self::getCancelledProductsOZON();
        $resultOzon = self::checkCancelledProductsOzon($cancelledProductsOzon);

        Log::channel('marketplace_api')->notice('Загрузка отмененных заказов завершена.');

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
            ->info('Получены отмененные заказы WB (статус в системе: '.$statusName.'):'.json_encode($orders));

        $unifiedOrders = [];

        if ($orders != []) {
            $body = [
                'orders' => $orders,
            ];

            try {
                $response = Http::accept('application/json')
                    ->withOptions(['verify' => false])
                    ->withHeaders(['Authorization' => self::getWbApiKey()])
                    ->post('https://marketplace-api.wildberries.ru/api/v3/orders/status', $body);

                if (! $response->ok()) {
                    Log::channel('marketplace_api')->error('ВНИМАНИЕ! Ошибка получения отмененных заказов из Wb');
                    Log::channel('marketplace_api')->error($body);
                    Log::channel('marketplace_api')->error(json_encode($response->object(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    Log::channel('marketplace_api')->error('Rate-Limit Headers: '.json_encode([
                        'x-ratelimit-limit' => $response->header('x-ratelimit-limit'),
                        'x-ratelimit-retry' => $response->header('x-ratelimit-retry'),
                        'x-ratelimit-reset' => $response->header('x-ratelimit-reset'),
                    ], JSON_UNESCAPED_UNICODE));

                    return [];
                }

                $orders = $response->object()->orders;

                foreach ($orders as $order) {
                    if ($order->wbStatus == 'declined_by_client' || $order->wbStatus == 'canceled_by_client') {
                        $unifiedOrders[] = [
                            'id' => $order->id,
                            'marketplace_id' => '2',
                            'status' => $order->wbStatus,
                        ];
                    }
                }
            } catch (Throwable $e) {
                Log::channel('marketplace_api')->error(
                    'ВНИМАНИЕ! Ошибка получения отмененных заказов из Wb: '.$e->getMessage()
                );

                return [];
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
            ->map(fn ($id) => (int) $id)
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
            ->map(fn ($id) => (int) $id)
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
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    private static function getCancelledProductsOZON(): array
    {
        $since = Carbon::now()->subDays(7)->format('Y-m-d\TH:i:s\Z'); // 7 дней назад
        $to = Carbon::now()->format('Y-m-d\TH:i:s\Z'); // сегодня

        try {
            $body = [
                'dir' => 'ASC',
                'limit' => 1000,
                'offset' => 0,
                'filter' => [
                    'since' => $since,
                    'to' => $to,
                    'status' => 'cancelled',
                ],
            ];

            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v3/posting/fbs/list', $body);

            if (! $response->ok()) {
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
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'ВНИМАНИЕ! Ошибка получения отмененных заказов из Ozon: '.$e->getMessage()
            );

            return [];
        }
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
                    if (! self::hasSkuInSystem($skus->sku)) {
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
                    'created_at' => Carbon::parse($order->order_created)->setTimezone('Europe/Moscow'),
                    'is_b2b' => $order->is_b2b ?? false,
                ]);

                foreach ($order->skus as $skus) {
                    $sku = self::hasSkuInSystem($skus->sku);

                    if (MarketplaceOrderItemService::hasReadyItem($sku)) {
                        Log::channel('marketplace_api')
                            ->info('Товар под заказ №'.$order->id.' уже имеется в системе.');

                        MarketplaceOrderItemService::reserveReadyItem($sku, $marketplaceOrder);

                        $marketplaceOrder->status = '13'; // в сборке
                        $marketplaceOrder->save();

                        $text = 'Поступил заказ на подбор со склада товара: '.
                            $sku->item->title.' - '.$sku->item->width.' x '.$sku->item->height;

                        Log::channel('marketplace_api')
                            ->notice('Отправляем сообщение в ТГ работающему кладовщику и админу: '.$text);

                        NotificationService::notifyAdmin($text);

                        foreach (UserService::getListStorekeepersWorkingToday() as $index => $user) {
                            NotificationService::notify($user, $text, queued: true, delaySeconds: $index + 1);
                        }
                    } else {
                        Log::channel('marketplace_api')
                            ->info('Заказ №'.$order->id.' добавлен в систему.');

                        MarketplaceOrderItemService::createItem($sku, $marketplaceOrder);

                        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceOrder->marketplace_id);

                        $materialName = $marketplaceOrder->items->first()->item->title;

                        $text = 'Поступил новый заказ '.$materialName.' на '.$marketplaceName;

                        Log::channel('marketplace_api')
                            ->notice('Отправляем сообщение в ТГ админу и работающим швеям: '.$text);

                        NotificationService::notifyAdmin($text);

                        foreach (UserService::getListSeamstressesWorkingToday() as $index => $user) {
                            NotificationService::notify($user, $text, queued: true, delaySeconds: $index + 1);
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
                    ->error('Ошибка при загрузке заказа №'.$order->id.': '.$e->getMessage());
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

        if (! empty($orders)) {
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

    public static function splittingOrder($order): bool
    {
        $postings = [];

        foreach ($order->skus as $product) {
            for ($i = 0; $i < $product->quantity; $i++) {
                $postings[] = [
                    'products' => [[
                        'product_id' => $product->sku,
                        'quantity' => 1,
                    ]],
                ];
            }
        }

        try {
            $body = [
                'posting_number' => $order->id,
                'postings' => $postings,
            ];

            $response = self::ozonRequest()
                ->post('https://api-seller.ozon.ru/v1/posting/fbs/split', $body);

            if (! $response->ok()) {
                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::channel('marketplace_api')->error(
                'Ошибка при разделении по товарам заказа '.$order.': '.$e->getMessage()
            );

            return false;
        }
    }

    public static function collectOrderOzon($orderId, $product): bool
    {
        return OzonApiService::collectOrder($orderId, $product);
    }

    public static function collectOrderWb($orderId): bool
    {
        return WbApiService::collectOrder($orderId);
    }

    private static function hasOrderInSystem($id): bool
    {
        return MarketplaceOrder::query()->where('order_id', $id)->exists();
    }

    private static function hasSkuInSystem($sku): ?Sku
    {
        return Sku::query()->where('sku', $sku)->first();
    }

    public static function getBarcodeOzonBySku(?string $sku): ?string
    {
        return OzonApiService::getBarcodeBySku($sku);
    }

    /**
     * Получает PDF-стикер для OZON FBS-заказа по номеру отправления.
     */
    public function getBarcodeOzon(mixed $orderId): object|false|null
    {
        return OzonApiService::getBarcode($orderId);
    }

    /**
     * Получает стикер для WB-заказа и возвращает его как PDF-поток.
     */
    public function getBarcodeWb(int $orderId): object|false|null
    {
        return WbApiService::getBarcode($orderId);
    }

    /**
     * Генерирует PDF с лентой стикеров для Ozon FBO-заказов.
     */
    public function getBarcodeOzonFBO(Collection $orders): \Illuminate\Http\Response
    {
        return OzonApiService::getBarcodeFBO($orders);
    }

    /**
     * Генерирует HTML-представление стикера для OZON FBO-заказа (для предпросмотра).
     */
    public function getBarcodeOzonFBOHtml(MarketplaceOrder $order): \Illuminate\View\View
    {
        return OzonApiService::getBarcodeFBOHtml($order);
    }

    /**
     * Генерирует PDF с лентой стикеров для WB FBO-заказов.
     */
    public function getBarcodeWBFBO(Collection $orders): \Illuminate\Http\Response
    {
        return WbApiService::getBarcodeFBO($orders);
    }

    public static function getItemWbBySku($sku): ?object
    {
        return WbApiService::getItemBySku($sku);
    }

    private static function checkCancelledProductsOzon(array $cancelledProductsOzon): array
    {
        $resultArray = [];

        Log::channel('marketplace_api')
            ->info('Получены отмененные заказы OZON:'.json_encode($cancelledProductsOzon));

        foreach ($cancelledProductsOzon as $product) {
            $order = MarketplaceOrder::query()
                ->where('order_id', $product->id)
                ->where('fulfillment_type', 'FBS')
                ->first();

            if ($order) {
                $item = $order->items->first();

                if (! $item) {
                    Log::channel('marketplace_api')
                        ->warning('Внимание! Заказ №'.$order->order_id.' НЕ отменен. Не найдены товары для этого заказа.');

                    continue;
                }

                switch ($item->status) {
                    case 0:
                        Log::channel('marketplace_api')->info('Заказа №'.$order->order_id.' удален.');

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
                        Log::channel('marketplace_api')->info('Заказа №'.$order->order_id.' изменен на FBO.');

                        $resultArray[] = [
                            'order_id' => $order->order_id,
                            'status' => 'изменен на FBO',
                        ];
                        $order->fulfillment_type = 'FBO';
                        $order->save();
                        break;
                    case 13:
                        Log::channel('marketplace_api')
                            ->info('Клиент отменил заказ №'.$order->order_id.' Пробуем его удалить из системы...');

                        if ($order->status != 13) {
                            break;
                        }

                        MarketplaceOrderItemService::restoreOrderFromHistory($order->items->first());

                        $hasItems = MarketplaceOrderItem::query()
                            ->where('marketplace_order_id', $order->id)
                            ->exists();

                        if ($hasItems) {
                            Log::channel('marketplace_api')
                                ->error('Внимание! Заказа №'.$order->order_id.' НЕ удален. Найдены товары для этого заказа.');
                            break;
                        }

                        $resultArray[] = [
                            'order_id' => $order->order_id,
                            'status' => 'удален',
                        ];

                        $order->delete();
                        Log::channel('marketplace_api')->info('Заказа №'.$order->order_id.' удален.');
                        break;
                }
            }
        }

        Log::channel('marketplace_api')
            ->info('Отмененные заказы обработаны:'.json_encode($resultArray));

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

            Log::channel('marketplace_api')->info('Заказа №'.$product->id.' удален.');

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
                ->info('Клиент отменил заказ №'.$product->id.' Пробуем его удалить из системы...');

            $order = MarketplaceOrder::query()
                ->where('order_id', $product->id)
                ->first();

            MarketplaceOrderItemService::restoreOrderFromHistory($order->items->first());

            $hasItems = MarketplaceOrderItem::query()
                ->where('marketplace_order_id', $order->id)
                ->exists();

            if ($hasItems) {
                Log::channel('marketplace_api')
                    ->error('Внимание! Заказа №'.$order->order_id.' НЕ удален. Найдены товары для этого заказа.');

                continue;
            }

            $resultArray[] = [
                'order_id' => $order->order_id,
                'status' => 'удален',
            ];

            $order->delete();
            Log::channel('marketplace_api')->info('Заказа №'.$order->order_id.' удален.');
        }

        return $resultArray;
    }

    private static function changeToFBOCancelledProductsWb(array $cancelledProductsWbInWorkStatus): array
    {
        $resultArray = [];

        foreach ($cancelledProductsWbInWorkStatus as $product) {

            Log::channel('marketplace_api')->info('Заказа №'.$product->id.' изменен на FBO.');

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
        return OzonApiService::getPostingNumberByBarcode($barcode);
    }

    /**
     * Ищем по стикеру в возвратах номер заказа.
     * Если не нашли, ищем по номеру стикера (сразу по номеру стикера нальзя - может быть пусто).
     */
    public static function getOzonPostingNumberByReturnBarcode($barcode): array|string
    {
        return OzonApiService::getPostingNumberByReturnBarcode($barcode);
    }

    public static function ozonSupply(MarketplaceSupply $marketplace_supply): bool
    {
        return OzonApiService::supply($marketplace_supply);
    }

    public static function wbSupply(MarketplaceSupply $marketplace_supply): bool
    {
        return WbApiService::supply($marketplace_supply);
    }

    public static function checkStatusSupplyOzon(MarketplaceSupply $marketplace_supply): bool
    {
        return OzonApiService::checkStatusSupply($marketplace_supply);
    }

    public static function getDocsSupplyOzon(MarketplaceSupply $marketplace_supply)
    {
        return OzonApiService::getDocsSupply($marketplace_supply);
    }

    public static function getBarcodeSupplyOzon(MarketplaceSupply $marketplace_supply)
    {
        return OzonApiService::getBarcodeSupply($marketplace_supply);
    }

    public static function getBarcodeSupplyWB(MarketplaceSupply $marketplace_supply)
    {
        return WbApiService::getBarcodeSupply($marketplace_supply);
    }

    public static function updateStatusOrderBySupplyWB(MarketplaceSupply $marketplace_supply): bool
    {
        return WbApiService::updateStatusOrderBySupply($marketplace_supply);
    }

    public static function updateStatusOrderBySupplyOzon(MarketplaceSupply $marketplace_supply): bool
    {
        return OzonApiService::updateStatusOrderBySupply($marketplace_supply);
    }

    public static function getStatusOrder(MarketplaceOrder $order)
    {
        return match ($order->marketplace_id) {
            Marketplace::OZON => OzonApiService::getStatusOrder($order),
            Marketplace::WB => WbApiService::getStatusOrder($order),
            default => null
        };
    }

    /**
     * Проверяет статус экземпляров заказа. И если он не соответствует "ship_available", то пытаемся добавить ГТД.
     */
    public static function getReturnReason(MarketplaceOrderItem $marketplace_item): string
    {
        return match ($marketplace_item->marketplaceOrder->marketplace_id) {
            Marketplace::OZON => OzonApiService::getReturnReason($marketplace_item),
            Marketplace::WB => WbApiService::getReturnReason($marketplace_item),
            default => '---',
        };
    }

    public static function getReturnsGiveoutPng(): ?array
    {
        return OzonApiService::getReturnsGiveoutPng();
    }

    public static function resetReturnsGiveoutBarcode(): ?array
    {
        return OzonApiService::resetReturnsGiveoutBarcode();
    }

    public static function getReturnsCompanyFbsInfo(): array
    {
        return OzonApiService::getReturnsCompanyFbsInfo();
    }

    public static function getReturnsGiveoutList(): array
    {
        return OzonApiService::getReturnsGiveoutList();
    }

    public static function getReturnsGiveoutInfo(int $giveoutId): ?array
    {
        return OzonApiService::getReturnsGiveoutInfo($giveoutId);
    }

    public static function getReturnsList(array $filter = [], int $limit = 100, $lastId = null): array
    {
        return OzonApiService::getReturnsList($filter, $limit, $lastId);
    }

    /**
     * Загружает склады OZON из API и добавляет в БД те, которых еще нет.
     */
    public static function syncWarehousesOzon(): int
    {
        return OzonApiService::syncWarehouses();
    }

    /**
     * Получает список складов продавца FBO из OZON API.
     */
    public static function getSellerWarehousesOzon(): array
    {
        return OzonApiService::getSellerWarehouses();
    }

    /**
     * Получает информацию о статусе черновика поставки OZON.
     */
    public static function getDraftInfoOzon(int $draftId): ?array
    {
        return OzonApiService::getDraftInfo($draftId);
    }

    /**
     * Получает список доступных складов для черновика поставки OZON.
     */
    public static function getDraftWarehousesOzon(int $draftId): array
    {
        return OzonApiService::getDraftWarehouses($draftId);
    }

    /**
     * Получает таймслоты для выбранного склада черновика OZON.
     */
    public static function getDraftTimeslotsOzon(int $draftId, string $supplyType, int $macrolocalClusterId, int $storageWarehouseId, string $dateFrom, string $dateTo): array
    {
        return OzonApiService::getDraftTimeslots($draftId, $supplyType, $macrolocalClusterId, $storageWarehouseId, $dateFrom, $dateTo);
    }

    /**
     * Создаёт черновик прямой поставки FBO через OZON API.
     */
    public static function createDraftDirectOzon(int $macrolocalClusterId, array $items): array
    {
        return OzonApiService::createDraftDirect($macrolocalClusterId, $items);
    }

    /**
     * Создаёт черновик кросс-докинг поставки FBO через OZON API.
     */
    public static function createDraftCrossdockOzon(int $macrolocalClusterId, int $sellerWarehouseId, array $items): array
    {
        return OzonApiService::createDraftCrossdock($macrolocalClusterId, $sellerWarehouseId, $items);
    }

    /**
     * Создаёт заявку на поставку из черновика через OZON API.
     */
    public static function createSupplyFromDraftOzon(int $draftId, int $macrolocalClusterId, int $storageWarehouseId, string $fromTime, string $toTime, string $supplyType): array
    {
        return OzonApiService::createSupplyFromDraft($draftId, $macrolocalClusterId, $storageWarehouseId, $fromTime, $toTime, $supplyType);
    }

    /**
     * Получает статус создания заявки на поставку из черновика OZON.
     */
    public static function getSupplyCreateStatusOzon(int $draftId): array
    {
        return OzonApiService::getSupplyCreateStatus($draftId);
    }

    /**
     * Отменяет заявку на поставку через OZON API.
     */
    public static function cancelSupplyOzon(int $orderId): array
    {
        return OzonApiService::cancelSupply($orderId);
    }

    /**
     * Получает статус отмены заявки на поставку через OZON API.
     */
    public static function getCancelSupplyStatusOzon(string $operationId): array
    {
        return OzonApiService::getCancelSupplyStatus($operationId);
    }

    /**
     * Загружает склады WB из supplies API и добавляет в БД те, которых еще нет.
     */
    public static function syncWarehousesWb(): int
    {
        return WbApiService::syncWarehouses();
    }

    /**
     * Получает список FBO-поставок из WB supplies API.
     */
    public static function getFboSuppliesWb(): array
    {
        return WbApiService::getFboSupplies();
    }

    /**
     * Получает детальную информацию по FBO-поставке из WB supplies API.
     */
    public static function getFboSupplyDetailWb(int $supplyId): array
    {
        return WbApiService::getFboSupplyDetail($supplyId);
    }

    /**
     * Получает товарный состав FBO-поставки из WB supplies API.
     */
    public static function getFboSupplyGoodsWb(int $supplyId): array
    {
        return WbApiService::getFboSupplyGoods($supplyId);
    }

    /**
     * Получает список заявок на поставку OZON в состоянии DATA_FILLING.
     *
     * @return array Массив order_ids или пустой массив при ошибке
     */
    public static function getSupplyOrderListOzon(): array
    {
        return OzonApiService::getSupplyOrderList();
    }

    /**
     * Получает детали заявки на поставку OZON.
     *
     * @return array Массив с данными заявки или пустой массив при ошибке
     */
    public static function getSupplyOrderDetailsOzon(int $orderId): array
    {
        return OzonApiService::getSupplyOrderDetails($orderId);
    }

    /**
     * Получает товары бандлов заявки на поставку OZON с пагинацией.
     *
     * @param  array<string>  $bundleIds  Массив ID бандлов
     * @return array Плоский массив всех товаров бандла
     */
    public static function getSupplyOrderBundleOzon(array $bundleIds): array
    {
        return OzonApiService::getSupplyOrderBundle($bundleIds);
    }

    /**
     * Создаёт грузоместо для короба OZON FBO-поставки.
     */
    public static function createCargoOzon(array $payload): array
    {
        return OzonApiService::createCargo($payload);
    }

    /**
     * Получает информацию о результате создания грузоместа OZON.
     */
    public static function getCargoCreateInfoOzon(string $operationId): array
    {
        return OzonApiService::getCargoCreateInfo($operationId);
    }

    /**
     * Создаёт этикетку (стикер) для грузоместа OZON.
     */
    public static function createCargoLabelOzon(string $supplyId, array $cargoIds): array
    {
        return OzonApiService::createCargoLabel($supplyId, $cargoIds);
    }

    /**
     * Получает информацию об этикетке грузоместа OZON (file_url).
     */
    public static function getCargoLabelOzon(string $operationId): array
    {
        return OzonApiService::getCargoLabel($operationId);
    }
}
