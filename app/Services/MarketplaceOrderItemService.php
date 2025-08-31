<?php

namespace App\Services;

use App\Models\MarketplaceOrderItem;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Throwable;

class MarketplaceOrderItemService
{
    public static function getFiltered($request): Builder
    {
        $status = $request->status ?? 'in_work';

        $statusId = match ($request->status) {
            'new' => 0,
            'done' => 3,
            'labeling' => 5,
            default => 4,
        };

        $items = MarketplaceOrderItem::query();

        $items = match ($statusId) {
            0 => $items->where('marketplace_order_items.status', 0),
            3 => $items->where('marketplace_order_items.status', 3),
            5 => $items->where('marketplace_order_items.status', 5),
            default => $items->where('marketplace_order_items.status', 4),
        };

        $items = $items->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->orderBy('marketplace_orders.fulfillment_type', 'asc')
//            ->orderBy('marketplace_orders.marketplace_id', 'asc')
            ->orderBy('marketplace_orders.created_at', 'asc')
            ->orderBy('marketplace_order_items.id', 'asc')
            ->select('marketplace_order_items.*');

        if(auth()->user()->role->name === 'seamstress' && $status != 'new') {
            $items = $items->where('marketplace_order_items.seamstress_id', auth()->user()->id);
        }

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

/*
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

        $maxCountOrderItems = self::getMaxQuantityOrdersToSeamstress();

        $seamstressId = auth()->user()->id;

        $maxStack = StackService::getMaxStackByUser($seamstressId)->max;

        if ($maxStack >= $maxCountOrderItems){
            return [
                'success' => false,
                'message' => 'Сначала вам необходимо закрыть все текущие заказы.'
            ];
        }

//        TO_DO скорее всего ненужная проверка.
//        $countOrderItemsBySeamstress = MarketplaceOrderItem::query()
//            ->whereIn('status', [4, 5])
//            ->where('seamstress_id', auth()->user()->id)
//            ->count();
//
//        if ($countOrderItemsBySeamstress > $maxCountOrderItems) {
//            return [
//                'success' => false,
//                'message' => 'Вы не можете взять больше ' . $maxCountOrderItems . ' заказов!'
//            ];
//        }

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

            //  добавляем +1 к стэку и максимуму в стэке.
            StackService::incrementStackAndMaxStack($seamstressId);

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
    }*/

    public static function cancelToSeamstress(MarketplaceOrderItem $marketplaceOrderItem): array
    {
        try {
            DB::beginTransaction();

            $logMessage =
                '    Отменен заказ № ' . $marketplaceOrderItem->marketplaceOrder->order_id .
                ' (товар #' . $marketplaceOrderItem->id . '). Холдирование материалов на пошив - удалено. Не выплаченная зарплата и бонусы - удалены.' . PHP_EOL .
                'Швея: ' . $marketplaceOrderItem->seamstress->name .
                ' (' . $marketplaceOrderItem->seamstress->id . ')' . PHP_EOL .
                'Инициатор: ' . auth()->user()->name . ' (' . auth()->user()->id . ')' . PHP_EOL;

            //  добавляем -1 к стэку и проверяем что если это последний заказ в стэке, то обнуляем стэк.
//            StackService::reduceStack($marketplaceOrderItem->seamstress_id);

            $marketplaceOrderItem->update([
                'status' => 0,
                'seamstress_id' => 0,
                'completed_at' => null
            ]);

            Transaction::query()
                ->where('marketplace_order_item_id', $marketplaceOrderItem->id)
                ->where('user_id', $marketplaceOrderItem->seamstress->id)
                ->where('status', '!=', 2)
                ->delete();

            $order = Order::query()
                ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id)
                ->first();

            MovementMaterial::query()
                ->where('order_id', $order->id)
                ->delete();

            $order->delete();

            Log::channel('erp')
                ->notice($logMessage);

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error($e->getMessage());

            Log::channel('erp')
                ->error('    Заказ № '.$marketplaceOrderItem->marketplaceOrder->order_id .' не удалось отменить!');

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
        $marketplaceOrderItemInWork = MarketplaceOrderItem::query()
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
        $seamstresses = User::query()
            ->where('role_id', '1')
            ->where('name', 'not like', '%Тест%')
            ->get();

        foreach ($seamstresses as $seamstress) {
            $seamstressesLargeSizeRating[$seamstress->id]['name'] = $seamstress->name;
            foreach ($dates as $date) {
                $startDate = $endDate = $date;

                $seamstressesLargeSizeRating[$seamstress->id][$date] = self::getRatingByDate($seamstress, $startDate, $endDate);
            }
        }

        return $seamstressesLargeSizeRating;
    }

    public static function getDatesByLargeSizeRating($daysAgo): array
    {
        $dates = [];
        $startDate = Carbon::now()->subDays($daysAgo + 6);

        for ($i = 0; $i < 7; $i++) {
            $dates[] = $startDate->copy()->addDays($i)->toDateString();
        }

        return $dates;
    }

    public static function getRatingByDate(mixed $seamstress, mixed $startDate, mixed $endDate): float|string
    {
        $seamstressRating = MarketplaceOrderItem::query()
            ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
            ->where('marketplace_order_items.seamstress_id', $seamstress->id)
            ->where('marketplace_order_items.status', 3)
            ->whereBetween('marketplace_order_items.completed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('SUM(marketplace_order_items.quantity * marketplace_items.width / 100) as total_volume, SUM(marketplace_order_items.quantity) as total_quantity')
            ->first();

        if ($seamstressRating && $seamstressRating->total_quantity > 0) {
            $averageVolume = $seamstressRating->total_volume / $seamstressRating->total_quantity;
            $result = round($averageVolume, 1);
        } else {
            $result = "0.0";
        }

        return $result;
    }

    public static function getSeamstressesRating()
    {
        return User::query()
            ->where('role_id', '1')
            ->where('name', 'not like', '%Тест%')
            ->get()
            ->map(function ($user) {
                $startDate = Carbon::now()->subDays(14)->toDateString();
                $startDate2 = Carbon::now()->subMonth()->toDateString();
                $endDate = Carbon::now()->toDateString();

                $user->ratingNow = MarketplaceOrderItemService::getRatingByDate($user, $endDate, $endDate);
                $user->rating2week = MarketplaceOrderItemService::getRatingByDate($user, $startDate, $endDate);
                $user->rating1month = MarketplaceOrderItemService::getRatingByDate($user, $startDate2, $endDate);
                return $user;
            });
    }

    public static function getItemsForLabeling(Request $request): Collection
    {
        $items = MarketplaceOrderItem::query()
            ->where('marketplace_order_items.status', '5')
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->select('marketplace_order_items.*');

        if ($request->has('seamstress_id')) {
            $items = $items->where('marketplace_order_items.seamstress_id', $request->seamstress_id);
        } else {
            $items = $items->where('marketplace_order_items.seamstress_id', 0);
        }

        if ($request->has('marketplace_id')) {
            $items = $items->where('marketplace_orders.marketplace_id', $request->marketplace_id);
        }

        return $items->get();
    }

    public static function getMaxQuantityOrdersToSeamstress()
    {
        return Setting::query()->where('name', 'max_quantity_orders_to_seamstress')->first()->value;
    }

    private static function checkSchedule(): array
    {
        if (ScheduleService::isEnabledSchedule()) {
            if (!ScheduleService::isWorkDay()) {
                return [
                    'success' => false,
                    'message' => 'Вы не можете взять заказ в нерабочий день!'
                ];
            }

            $nowTime = Carbon::now();
            $startWorkDay = ScheduleService::getStartWorkDay();
            $endWorkDay = ScheduleService::getEndWorkDay();

            if (
                $nowTime->lt(Carbon::createFromFormat('H:i', $startWorkDay))
                || $nowTime->gte(Carbon::createFromFormat('H:i', $endWorkDay))
            ) {
                return [
                    'success' => false,
                    'message' => 'Вы не можете взять заказ в нерабочее время!'
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'OK'
        ];
    }

    private static function checkMaxStack(): array
    {
        $countOrderItemsBySeamstress = MarketplaceOrderItem::query()
            ->whereIn('status', [4, 5])
            ->where('seamstress_id', auth()->user()->id)
            ->count();

        $maxCountOrderItems = self::getMaxQuantityOrdersToSeamstress();

        if ($countOrderItemsBySeamstress >= $maxCountOrderItems) {
            return [
                'success' => false,
                'message' => 'Вы не можете взять больше ' . $maxCountOrderItems . ' заказов!'
            ];
        }

//        $seamstressId = auth()->user()->id;
//
//        $maxStack = StackService::getMaxStackByUser($seamstressId)->max;
//        if ($maxStack >= $maxCountOrderItems){
//            return [
//                'success' => false,
//                'message' => 'Достигнут максимум заказов. Сначала вам необходимо закрыть все текущие заказы.'
//            ];
//        }

        return [
            'success' => true,
            'message' => 'OK'
        ];
    }

    private static function checkMaterials($marketplaceOrderItem): array
    {
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

        return [
            'success' => true,
            'message' => 'OK'
        ];
    }

    private static function assignOrderToSeamstress($marketplaceOrderItem): array
    {
        try {
            $marketplaceItem = $marketplaceOrderItem->item()->first();
            $materialConsumptions = $marketplaceItem->consumption;
            $quantityOrderItem = $marketplaceOrderItem->quantity;
            $seamstressId = auth()->user()->id;

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

            //  добавляем +1 к стэку и максимуму в стэке.
//            StackService::incrementStackAndMaxStack($seamstressId);

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

    public static function getNewOrderItem(): array
    {
        $result = self::checkSchedule();
        if (!$result['success']) {
            return $result;
        }

        $result = self::checkMaxStack();
        if (!$result['success']) {
            return $result;
        }

        $items = MarketplaceOrderItem::query()
            ->where('marketplace_order_items.status', 0)
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_items', 'marketplace_order_items.marketplace_item_id', '=', 'marketplace_items.id');

        // Персональный приоритет заказов
        $items = match (auth()->user()->orders_priority) {
            'fbo' => $items->where('marketplace_orders.fulfillment_type', 'FBO'),
            'fbo_200' => $items->where('marketplace_orders.fulfillment_type', 'FBO')
                ->where('marketplace_items.width', 200),
            default => $items
        };

        // Глобальный приоритет заказов
        $orders_priority = Setting::query()
            ->where('name', 'orders_priority')
            ->first();

        $items = match ($orders_priority->value) {
            'ozon' => $items->orderBy('marketplace_orders.marketplace_id', 'asc'),
            'wb' => $items->orderBy('marketplace_orders.marketplace_id', 'desc'),
            default => $items
        };

        $items = $items->orderBy('marketplace_orders.created_at', 'asc')
            ->orderBy('marketplace_orders.fulfillment_type', 'asc')
            ->orderBy('marketplace_order_items.id', 'asc')
            ->select('marketplace_order_items.*')
            ->get();

        foreach ($items as $marketplaceOrderItem) {
            $item = $marketplaceOrderItem->item()->first();
            $result = self::checkMaterials($marketplaceOrderItem);

            if ($result['success']) {
                $marketplaceName = match ($marketplaceOrderItem->marketplaceOrder->marketplace_id) {
                    1 => 'OZON',
                    2 => 'WB',
                    default => '---',
                };

                $text = 'Товар ' . $marketplaceName . ' #' . $marketplaceOrderItem->id .
                    ' (' . $item->title . ' '. $item->width . 'x' . $item->height .
                    ') взяла в работу швея: ' . auth()->user()->name;

                TgService::sendMessage(config('telegram.admin_id'), $text);

                TgService::sendMessage(
                    auth()->user()->tg_id,
                    'Вы взяли в работу заказ # '
                    . $marketplaceOrderItem->marketplaceOrder->order_id . ' ('. $marketplaceName .'): '
                    . $item->title . ' '. $item->width . 'x' . $item->height
                );

                Log::channel('erp')->info($text);

                return self::assignOrderToSeamstress($marketplaceOrderItem);
            }

            $text = 'На товар ' . $item->title . ' '. $item->width . 'x' . $item->height . ' недостаточно материала';

            TgService::sendMessage(config('telegram.admin_id'), $text);
        }

        return [
            'success' => false,
            'message' => 'Нет доступных заказов'
        ];
    }

}
