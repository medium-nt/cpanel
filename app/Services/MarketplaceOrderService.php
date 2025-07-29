<?php

namespace App\Services;

use App\Http\Requests\StoreMarketplaceOrderRequest;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
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

        if (
            empty($itemIds) || empty($quantities)
        ) {
            return back()->withErrors([
                'error' => 'Заполните правильно список товаров и количество.'
            ]);
        }

        try {
            DB::beginTransaction();

            $marketplaceOrder = MarketplaceOrder::query()->create([
                'order_id' => $request->order_id,
                'marketplace_id' => $request->marketplace_id,
                'fulfillment_type' => $request->fulfillment_type,
                'status' => 0,
            ]);

            foreach ($itemIds as $key => $item_id) {
                if (!is_null($quantities[$key]) && $quantities[$key] > 0) {
                    $movementData['marketplace_order_id'] = $marketplaceOrder->id;
                    $movementData['marketplace_item_id'] = $item_id;
                    $movementData['quantity'] = $quantities[$key];
                    $movementData['price'] = 0;

                    MarketplaceOrderItem::query()->create($movementData);
                }
            }

            $marketplaceName = match ($marketplaceOrder->marketplace_id) {
                '1' => 'OZON',
                '2' => 'WB',
                default => '---',
            };

            Log::channel('erp')
                ->notice('    Вручную добавлен новый заказ: ' . $marketplaceOrder->order_id . ' (' . $marketplaceName . ')');

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return false;
        }

        return true;
    }

}
