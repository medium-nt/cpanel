<?php

namespace App\Services;

use App\Models\MarketplaceOrderItem;
use App\Models\MovementMaterial;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class MarketplaceOrderItemService
{
    public static function getFiltered($request): Builder
    {
        $status = $request->status ?? 'in_work';

        $items = MarketplaceOrderItem::query()
            ->when($status === 'new', function ($query) {
                return $query->where('marketplace_order_items.status', '0');
            })
            ->when($status === 'in_work' || $status === 'done', function ($query) use ($status) {
                return $query->where('marketplace_order_items.status', $status === 'in_work' ? 4 : 3)
                    ->when(auth()->user()->role->name === 'seamstress', function ($query) {
                        return $query->where('seamstress_id', auth()->user()->id);
                    });
            })
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->orderBy('marketplace_orders.fulfillment_type', 'asc')
            ->orderBy('marketplace_orders.marketplace_id', 'asc')
            ->orderBy('marketplace_orders.created_at', 'asc')
            ->select('marketplace_order_items.*')
        ;


        if ($request->has('marketplace_order_items.seamstress_id') && $status != 'new') {
            $items = $items->where('marketplace_order_items.seamstress_id', $request->seamstress_id);
        }

        if ($request->has('marketplace_order_items.date_start') && $status != 'new') {
            $items = $items->where('marketplace_order_items.created_at', '>', $request->date_start);
        }

        if ($request->has('marketplace_order_items.date_end') && $status != 'new') {
            $items = $items->where('marketplace_order_items.created_at', '<', $request->date_end);
        }


        return $items;
    }

    public static function acceptToSeamstress($marketplaceOrderItem): array
    {
        $quantityOrderItem = $marketplaceOrderItem->quantity;

        $marketplaceItem = $marketplaceOrderItem->item()->first();
        $materialConsumptions = $marketplaceItem->consumption;

        if ($materialConsumptions->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Для этого заказа не указаны материалы!'
            ];
        }

        foreach ($materialConsumptions as $materialConsumption) {
            $materialId = $materialConsumption->material_id;
            $materialConsumptionQuantity = $materialConsumption->quantity;

            $materialInWorkhouse = InventoryService::materialInWorkshop($materialId);

            if ($materialInWorkhouse < $materialConsumptionQuantity * $quantityOrderItem) {
                return [
                    'success' => false,
                    'message' => 'Для этого заказа на производстве недостаточно материала!'
                ];
            }
        }

        try {
            DB::beginTransaction();

            $marketplaceOrderItem->update([
                'status' => 4,
                'seamstress_id' => auth()->user()->id
            ]);

            $order = Order::query()->create([
                'type_movement' => 3,
                'status' => 4,
                'seamstress_id' => auth()->user()->id,
                'comment' => 'По заказу No: ' . $marketplaceOrderItem->marketplaceOrder->order_id,
                'marketplace_order_id' => $marketplaceOrderItem->marketplaceOrder->id
            ]);

            foreach ($materialConsumptions as $item) {
                $movementData['material_id'] = $item->material_id;
                $movementData['quantity'] = $item->quantity * $quantityOrderItem;
                $movementData['order_id'] = $order->id;

                MovementMaterial::query()->create($movementData);
            }

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Внутренняя ошибка'
            ];
        }

        return [
            'success' => true,
            'message' => 'Заказ принят'
        ];
    }

    public static function cancelToSeamstress(MarketplaceOrderItem $marketplaceOrderItem): array
    {
        try {
            DB::beginTransaction();

            $marketplaceOrderItem->update([
                'status' => 0,
                'seamstress_id' => 0,
            ]);

            $order = Order::query()
                ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id)
                ->first();

            MovementMaterial::query()
                ->where('order_id', $order->id)
                ->delete();

            $order->delete();

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Внутренняя ошибка'
            ];
        }

        return [
            'success' => true,
            'message' => 'Заказ отменен'
        ];
    }

}
