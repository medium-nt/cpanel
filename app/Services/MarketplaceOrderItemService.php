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
                return $query->where('status', '0');
            })
            ->when($status === 'in_work' || $status === 'done', function ($query) use ($status) {
                return $query->where('status', $status === 'in_work' ? 4 : 3)
                    ->when(auth()->user()->role->name === 'seamstress', function ($query) {
                        return $query->where('seamstress_id', auth()->user()->id);
                    });
            });

        if ($request->has('seamstress_id') && $status != 'new') {
            $items = $items->where('seamstress_id', $request->seamstress_id);
        }

        if ($request->has('date_start') && $status != 'new') {
            $items = $items->where('created_at', '>', $request->date_start);
        }

        if ($request->has('date_end') && $status != 'new') {
            $items = $items->where('created_at', '<', $request->date_end);
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

}
