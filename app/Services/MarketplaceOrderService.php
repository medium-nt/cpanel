<?php

namespace App\Services;

use App\Http\Requests\StoreMarketplaceOrderRequest;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Models\Shelf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketplaceOrderService
{
    public static function store(StoreMarketplaceOrderRequest $request): bool|RedirectResponse
    {
        $itemIds = $request->input('item_id', []);
        $quantities = $request->input('quantity', []);

        // Фильтруем пустые значения и собираем товары
        $items = [];
        foreach ($itemIds as $index => $itemId) {
            if (! empty($itemId) && ! empty($quantities[$index])) {
                $items[] = [
                    'item_id' => (int) $itemId,
                    'quantity' => (int) $quantities[$index],
                ];
            }
        }

        if (empty($itemIds) || empty($quantities)) {
            return back()->withErrors([
                'error' => 'Заполните правильно список товаров и количество.',
            ]);
        }

        try {
            DB::beginTransaction();

            if ($request->fulfillment_type == 'FBO') {
                $orderCounter = 1;
                foreach ($items as $item) {
                    for ($i = 1; $i <= $item['quantity']; $i++) {
                        $orderIndex = '-'.$orderCounter++;
                        self::addMarketplaceOrder($request, $orderIndex, $item['item_id']);
                    }
                }
            } else {
                self::addMarketplaceOrder($request, '', $items[0]['item_id']);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::channel('orders')
                ->error($e->getMessage());

            return false;
        }

        return true;
    }

    private static function addMarketplaceOrder(StoreMarketplaceOrderRequest $request, $orderIndex, $itemId): void
    {
        $marketplaceOrder = MarketplaceOrder::query()->create([
            'order_id' => $request->order_id.$orderIndex,
            'marketplace_id' => $request->marketplace_id,
            'fulfillment_type' => $request->fulfillment_type,
            'status' => 0,
            'cluster' => $request->cluster ?? null,
        ]);

        MarketplaceOrderItem::query()->create([
            'marketplace_order_id' => $marketplaceOrder->id,
            'marketplace_item_id' => $itemId,
            'quantity' => 1,
            'price' => 0,
        ]);

        $marketplaceName = self::getMarketplaceName($request->marketplace_id);

        Log::channel('orders')
            ->notice('Вручную добавлен новый заказ: '.$request->order_id.$orderIndex.' ('.$marketplaceName.')');
    }

    public static function getMarketplaceName(string $marketplace_id): string
    {
        return match ($marketplace_id) {
            '1' => 'OZON',
            '2' => 'WB',
            default => '---',
        };
    }

    public static function pickupOrders(): Builder
    {
        return MarketplaceOrder::query()
            ->where('status', 13);
    }

    public static function groupPickupOrders($orders): array
    {
        $grouped = [];
        foreach ($orders as $order) {
            /** @var MarketplaceOrder $order */
            $itemModel = $order->items->first()->item;

            $sameItems = MarketplaceOrderItem::query()
                ->where('marketplace_item_id', $itemModel->id)
                ->whereIn('status', [11, 13])
                ->get();

            $shelfStats = Shelf::query()
                ->whereIn('id', $sameItems->pluck('shelf_id')->filter())
                ->get()
                ->map(function ($shelf) use ($sameItems) {
                    $count = $sameItems->where('shelf_id', $shelf->id)->count();

                    return (object) [
                        'shelf' => $shelf,
                        'quantity' => $count,
                    ];
                });

            $grouped[$order->id] = [
                'itemName' => "{$itemModel->title} {$itemModel->width}×{$itemModel->height}",
                'shelfStats' => $shelfStats,
            ];
        }

        return $grouped;
    }

    public static function assembledOrders(): Collection
    {
        return MarketplaceOrder::query()
            ->where('status', 5)
            ->whereHas('items', function ($query) {
                $query->where('status', 13);
            })
            ->get();
    }

    public static function hasShippedOrdersBySupply(MarketplaceSupply $marketplace_supply): bool
    {
        $query = MarketplaceOrder::query()
            ->where('supply_id', $marketplace_supply->id);

        $status = match ($marketplace_supply->marketplace_id) {
            1 => 'awaiting_deliver',
            2 => 'confirm',
            default => '---',
        };

        $orders = $query
            //  ->where('marketplace_status', '!=', $status)
            ->where(function ($q) use ($status) {
                $q->where('marketplace_status', '!=', $status)
                    ->orWhereNull('marketplace_status');
            })
            //  могут попасться заказы со статусом "отменено"!
            //  ->whereNotIn('marketplace_status', ['cancelled', 'cancel'])
            ->get();

        if ($orders->isNotEmpty()) {
            Log::channel('marketplace_supplies')
                ->error('При проверке статусов поставки найдены заказы со статусом отличным от '.$status, [
                    'orders' => $orders->pluck('marketplace_status', 'order_id')->toArray(),
                ]);

            return true;
        }

        return false;
    }

    /**
     * Удаляет заказ, если все его позиции имеют статус 0 (новый).
     *
     * @param  MarketplaceOrder  $marketplaceOrder  Заказ для удаления
     * @return bool true если заказ удалён, false если удалить нельзя (товары уже в работе)
     */
    public static function delete(MarketplaceOrder $marketplaceOrder): bool
    {
        if ($marketplaceOrder->items->some(function ($item) {
            return $item->status != 0;
        })) {
            return false;
        }

        $marketplaceOrder->delete();

        Log::channel('orders')
            ->notice('Заказ №'.$marketplaceOrder->order_id.' удалён.');

        return true;
    }

    /**
     * Удаляет все заказы в статусе "новый" (status = 0) указанной поставки.
     *
     * @param  int  $supplyId  ID поставки
     * @return array ['deleted' => int, 'skipped' => int]
     */
    public static function deleteNewOrdersBySupply(int $supplyId): array
    {
        $orders = MarketplaceOrder::query()
            ->where('supply_id', $supplyId)
            ->where('status', 0)
            ->get();

        $deleted = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            if (self::delete($order)) {
                $deleted++;
            } else {
                $skipped++;
            }
        }

        if ($deleted > 0) {
            Log::channel('orders')
                ->notice('Из поставки #'.$supplyId.' удалено новых заказов: '.$deleted.', пропущено: '.$skipped.'.');
        }

        return [
            'deleted' => $deleted,
            'skipped' => $skipped,
        ];
    }

    /**
     * Отвязывает от поставки заказы, не готовые к отгрузке: без короба и не «новые».
     *
     * Сам заказ не удаляется — только обнуляется supply_id, чтобы он остался
     * в системе, но не попал в отгрузку.
     *
     * @param  int  $supplyId  ID поставки
     * @return array{detached: int} Количество отвязанных заказов
     */
    public static function detachNotReadyOrdersBySupply(int $supplyId): array
    {
        $detached = MarketplaceOrder::query()
            ->where('supply_id', $supplyId)
            ->whereNull('box_id')
            ->where('status', '!=', 0)
            ->update(['supply_id' => null]);

        if ($detached > 0) {
            Log::channel('orders')
                ->notice('От поставки #'.$supplyId.' отвязано не готовых заказов: '.$detached.'.');
        }

        return ['detached' => $detached];
    }
}
