<?php

namespace App\Services;

use App\Http\Requests\StoreMarketplaceOrderRequest;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
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

        if (empty($itemIds) || empty($quantities)) {
            return back()->withErrors([
                'error' => 'Заполните правильно список товаров и количество.',
            ]);
        }

        try {
            DB::beginTransaction();

            if ($request->fulfillment_type == 'FBO') {
                $quantity = $request->input('quantity', [])[0];
                for ($i = 1; $i <= $quantity; $i++) {
                    $orderIndex = '-' . $i;
                    self::addMarketplaceOrder($request, $orderIndex);
                }
            } else {
                self::addMarketplaceOrder($request, '');
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return false;
        }

        return true;
    }

    private static function addMarketplaceOrder(StoreMarketplaceOrderRequest $request, $orderIndex): void
    {
        $marketplaceOrder = MarketplaceOrder::query()->create([
            'order_id' => $request->order_id . $orderIndex,
            'marketplace_id' => $request->marketplace_id,
            'fulfillment_type' => $request->fulfillment_type,
            'status' => 0,
        ]);

        MarketplaceOrderItem::query()->create([
            'marketplace_order_id' => $marketplaceOrder->id,
            'marketplace_item_id' => $request->item_id[0],
            'quantity' => 1,
            'price' => 0,
        ]);

        $marketplaceName = self::getMarketplaceName($request->marketplace_id);

        Log::channel('erp')
            ->notice('Вручную добавлен новый заказ: ' . $request->order_id . $orderIndex . ' (' . $marketplaceName . ')');
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

                    return (object)[
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
}
