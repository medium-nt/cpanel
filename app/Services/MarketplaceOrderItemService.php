<?php

namespace App\Services;

use App\Models\MarketplaceOrderItem;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
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
                        return $query->where('marketplace_order_items.seamstress_id', auth()->user()->id);
                    });
            })
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->orderBy('marketplace_orders.fulfillment_type', 'asc')
            ->orderBy('marketplace_orders.marketplace_id', 'asc')
            ->orderBy('marketplace_orders.created_at', 'asc')
            ->orderBy('marketplace_order_items.id', 'asc')
            ->select('marketplace_order_items.*');

        if ($request->has('seamstress_id') && $status != 'new') {
            $items = $items->where('marketplace_order_items.seamstress_id', $request->seamstress_id);
        }

        if ($request->has('date_start') && $status == 'in_work') {
            $items = $items->where('marketplace_order_items.created_at', '>=', $request->date_start);
        }

        if ($request->has('date_start') && $status == 'done') {
            $items = $items->where('marketplace_order_items.completed_at', '>=', $request->date_start);
        }

        if ($request->has('marketplace_id')) {
            $items = $items->where('marketplace_orders.marketplace_id', $request->marketplace_id);
        }

        $dateEndWithTime = Carbon::parse($request->date_end)->endOfDay();

        if ($request->has('date_end') && $status == 'in_work') {
            $items = $items->where('marketplace_order_items.created_at', '<=', $dateEndWithTime);
        }

        if ($request->has('date_end') && $status == 'done') {
            $items = $items->where('marketplace_order_items.completed_at', '<=', $dateEndWithTime);
        }

        return $items;
    }

    public static function acceptToSeamstress($marketplaceOrderItem): array
    {
        if (ScheduleService::isEnabledSchedule()) {
            if (!ScheduleService::isWorkDay()) {
                return [
                    'success' => false,
                    'message' => 'Вы не можете взять заказ в нерабочий день!'
                ];
            }

            $nowTime = Carbon::now();
            if ($nowTime->lt(Carbon::createFromFormat('H:i:s', '07:00:00')) ||
                $nowTime->gte(Carbon::createFromFormat('H:i:s', '20:00:00'))) {
                return [
                    'success' => false,
                    'message' => 'Вы не можете взять заказ в нерабочее время! Сейчас ' . $nowTime->format('H:i:s') . '!'
                ];
            }
        }

        $countOrderItemsBySeamstress = MarketplaceOrderItem::query()
            ->where('status', 4)
            ->where('seamstress_id', auth()->user()->id)
            ->count();

        if ($countOrderItemsBySeamstress > 5) {
            return [
                'success' => false,
                'message' => 'Вы не можете принять больше 10 заказов!'
            ];
        }

        $marketplaceItem = $marketplaceOrderItem->item()->first();
        $materialConsumptions = $marketplaceItem->consumption;

        if ($materialConsumptions->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Для этого заказа не указаны материалы!'
            ];
        }

        $quantityOrderItem = $marketplaceOrderItem->quantity;

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
                'completed_at' => null
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

    public static function toWork(): int
    {
        $marketplaceOrderItemInWork =MarketplaceOrderItem::query()
            ->where('status', 4);

        if (auth()->user()->role->name === 'seamstress') {
            $marketplaceOrderItemInWork = $marketplaceOrderItemInWork
                ->where('seamstress_id', auth()->id());
        }

        return $marketplaceOrderItemInWork->sum('quantity');
    }

    public static function new(): int
    {
        return MarketplaceOrderItem::query()
            ->where('status', 0)
            ->sum('quantity');
    }

    public static function urgent(): int
    {
        return MarketplaceOrderItem::query()
            ->join('marketplace_orders',
                'marketplace_orders.id',
                '=',
                'marketplace_order_items.marketplace_order_id'
            )
            ->whereIn('marketplace_order_items.status', [0, 4])
            ->where('marketplace_orders.fulfillment_type', 'FBS')
            ->sum('quantity');
    }

    public static function getSeamstressesLargeSizeRating(array $dates): array
    {
        $seamstressesLargeSizeRating = [];
        $seamstresses = User::query()->where('role_id', '1')->get();

        foreach ($seamstresses as $seamstress) {
            $seamstressesLargeSizeRating[$seamstress->id]['name'] = $seamstress->name;
            foreach ($dates as $date) {
                $seamstressRating = MarketplaceOrderItem::query()
                    ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
                    ->where('marketplace_order_items.seamstress_id', $seamstress->id)
                    ->whereDate('marketplace_order_items.completed_at', $date)
                    ->where('marketplace_order_items.status', 3)
                    ->selectRaw('SUM(marketplace_order_items.quantity * marketplace_items.width / 100) as total_volume, SUM(marketplace_order_items.quantity) as total_quantity')
                    ->first();

                if ($seamstressRating && $seamstressRating->total_quantity > 0) {
                    $averageVolume = $seamstressRating->total_volume / $seamstressRating->total_quantity;
                    $seamstressesLargeSizeRating[$seamstress->id][$date] = $averageVolume;
                } else {
                    $seamstressesLargeSizeRating[$seamstress->id][$date] = null;
                }
            }
        }

        return $seamstressesLargeSizeRating;
    }

    public static function getDatesByLargeSizeRating(): array
    {
        $dates = [];
        $startDate = \Illuminate\Support\Carbon::now()->subWeek()->startOfWeek();

        for ($i = 0; $i < 7; $i++) {
            $dates[] = $startDate->copy()->addDays($i)->toDateString();
        }

        return $dates;
    }
}
