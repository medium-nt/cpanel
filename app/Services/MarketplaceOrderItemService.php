<?php

namespace App\Services;

use App\Jobs\SendTelegramMessageJob;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderHistory;
use App\Models\MarketplaceOrderItem;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Sku;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketplaceOrderItemService
{
    public static function getFiltered($request): Builder
    {
        $status = match (auth()->user()->role->name) {
            'cutter' => $request->status ?? 'cutting',
            'seamstress' => $request->status ?? 'in_work',
            default => $request->status ?? 'new',
        };

        $items = MarketplaceOrderItem::query();

        $items = $items->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->orderBy('marketplace_orders.fulfillment_type', 'asc')
            ->orderBy('marketplace_orders.created_at', 'asc')
            ->orderBy('marketplace_order_items.id', 'asc')
            ->select('marketplace_order_items.*');

        if($request->has('search') && (auth()->user()->isAdmin() || auth()->user()->isStorekeeper())) {
            if (mb_strlen(trim($request->search)) == 15) {
                $request->search = MarketplaceApiService::getOzonPostingNumberByBarcode($request->search);
            }
            $items = $items
                ->where('marketplace_orders.order_id', 'like', '%' . $request->search . '%')
                ->orWhere('part_b', $request->search)
                ->orWhere('barcode', $request->search);
        } else {
            $items = match ($status) {
                'new' => $items->where('marketplace_order_items.status', 0),
                'in_work' => $items->where('marketplace_order_items.status', 4),
                'done' => $items->where('marketplace_order_items.status', 3),
                'labeling' => $items->where('marketplace_order_items.status', 5),
                'cutting' => $items->where('marketplace_order_items.status', 7),
                'cut' => $items->where('marketplace_order_items.status', 8),
                default => $items,
            };
        }

        if(auth()->user()->isSeamstress() && $status != 'new') {
            $items = $items->where('marketplace_order_items.seamstress_id', auth()->user()->id);
        }

        if(auth()->user()->isCutter() && $status != 'new') {
            $items = $items->where('marketplace_order_items.cutter_id', auth()->user()->id);
        }

        if ($request->has('user_id') && $status != 'new') {
            $items = $items->where(function ($query) use ($request) {
                $query->where('marketplace_order_items.seamstress_id', $request->user_id)
                    ->orWhere('marketplace_order_items.cutter_id', $request->user_id);
            });
        }

        if ($request->has('date_start') && ($status == 'in_work' || $status == 'cutting')) {
            $items = $items->where('marketplace_order_items.created_at', '>=', $request->date_start);
        }

        if ($request->has('date_start') && $status == 'done') {
            $items = $items->where('marketplace_order_items.completed_at', '>=', $request->date_start);
        }

        if ($request->has('marketplace_id')) {
            $items = $items->where('marketplace_orders.marketplace_id', $request->marketplace_id);
        }

        $dateEndWithTime = Carbon::parse($request->date_end)->endOfDay();

        if ($request->has('date_end') && ($status == 'in_work' || $status == 'cutting')) {
            $items = $items->where('marketplace_order_items.created_at', '<=', $dateEndWithTime);
        }

        if ($request->has('date_end') && $status == 'done') {
            $items = $items->where('marketplace_order_items.completed_at', '<=', $dateEndWithTime);
        }

        return $items;
    }

    public static function cancelToSeamstress(MarketplaceOrderItem $marketplaceOrderItem): array
    {
        if (!in_array($marketplaceOrderItem->status, [4, 5, 7])) {
            return [
                'success' => false,
                'message' => 'Заказ с таким статусом не может быть отменен'
            ];
        }

        try {
            DB::beginTransaction();

            $logMessage = '';

            //  если на раскрое
            if ($marketplaceOrderItem->status == 7) {
                $logMessage =
                    'Отменен закрой заказа № ' . $marketplaceOrderItem->marketplaceOrder->order_id .
                    ' (товар #' . $marketplaceOrderItem->id . '). Холдирование материалов на закрой - удалено.' . PHP_EOL .
                    'Закройщик: ' . $marketplaceOrderItem->cutter->name .
                    ' (' . $marketplaceOrderItem->cutter->id . ')' . PHP_EOL .
                    'Инициатор: ' . auth()->user()->name . ' (' . auth()->user()->id . ')' . PHP_EOL;

                $marketplaceOrderItem->status = 0;
                $marketplaceOrderItem->cutter_id = null;
                $marketplaceOrderItem->completed_at = null;
                $marketplaceOrderItem->save();

                $order = Order::query()
                    ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id)
                    ->first();

                MovementMaterial::query()
                    ->where('order_id', $order->id)
                    ->delete();

                $order->delete();
            }

            //  если на пошиве, стикеровке или уже выполнен
            if ($marketplaceOrderItem->status == 4 || $marketplaceOrderItem->status == 5 || $marketplaceOrderItem->status == 3) {
                $logMessage =
                    'Отменен пошив заказа № ' . $marketplaceOrderItem->marketplaceOrder->order_id .
                    ' (товар #' . $marketplaceOrderItem->id . '). Холдирование материалов на пошив - удалено. Не выплаченная зарплата и бонусы - удалены.' . PHP_EOL .
                    'Швея: ' . $marketplaceOrderItem->seamstress->name .
                    ' (' . $marketplaceOrderItem->seamstress->id . ')' . PHP_EOL .
                    'Инициатор: ' . auth()->user()->name . ' (' . auth()->user()->id . ')' . PHP_EOL;

                $marketplaceOrderItem->status = ($marketplaceOrderItem->cutter_id) ? 8 : 0;
                $marketplaceOrderItem->seamstress_id = 0;
                $marketplaceOrderItem->completed_at = null;
                $marketplaceOrderItem->save();

                $order = Order::query()
                    ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id);

                if ($marketplaceOrderItem->cutter_id) {
                    $order = $order->whereNull('cutter_id');
                }

                $order = $order->first();

                MovementMaterial::query()
                    ->where('order_id', $order->id)
                    ->delete();

                $order->delete();
            }

            Log::channel('erp')
                ->notice($logMessage);

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error($e->getMessage());

            Log::channel('erp')
                ->error('Заказ № ' . $marketplaceOrderItem->marketplaceOrder->order_id . ' не удалось отменить!');

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

        if (auth()->user()->isSeamstress()) {
            $marketplaceOrderItemInWork = $marketplaceOrderItemInWork
                ->where('seamstress_id', auth()->id());
        }

        return $marketplaceOrderItemInWork->sum('quantity');
    }

    public static function toCutting(): int
    {
        $marketplaceOrderItemInWork = MarketplaceOrderItem::query()
            ->where('status', 7);

        if (auth()->user()->isCutter()) {
            $marketplaceOrderItemInWork = $marketplaceOrderItemInWork
                ->where('cutter_id', auth()->id());
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

    public static function getRating(): Collection|\Illuminate\Support\Collection
    {
        return User::query()
            ->whereIn('role_id', [1, 4])
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

        if ($request->has('user_id')) {
            $items = $items->where('marketplace_order_items.seamstress_id', $request->user_id);
        } else {
            $items = $items->where('marketplace_order_items.seamstress_id', 0);
        }

        if ($request->has('marketplace_id')) {
            $items = $items->where('marketplace_orders.marketplace_id', $request->marketplace_id);
        }

        return $items->get();
    }

    public static function getMaxQuantityOrdersToUserRole()
    {
        $field = match (auth()->user()->role->name) {
            'seamstress' => 'max_quantity_orders_to_seamstress',
            'cutter' => 'max_quantity_orders_to_cutter',
            default => false,
        };

        if (!$field) {
            return 0;
        }

        return Setting::query()->where('name', $field)->first()->value;
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

            if (!ScheduleService::hasWorkDayStarted()) {
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

    private static function checkMaxStack(User $user): array
    {
        try {
            $query = MarketplaceOrderItem::query();

            $orderItemsByUser = match ($user->role->name) {
                'seamstress' => $query->where('seamstress_id', $user->id)
                    ->whereIn('status', [4, 5]),
                'cutter' => $query->where('cutter_id', $user->id)
                    ->where('status', 7),
                default => throw new \Exception('Недопустимая роль: ' . $user->role->name),
            };

            $maxCountOrderItems = self::getMaxQuantityOrdersToUserRole();

            if ($orderItemsByUser->count() >= $maxCountOrderItems) {
                return [
                    'success' => false,
                    'message' => 'Вы не можете взять больше ' . $maxCountOrderItems . ' заказов!'
                ];
            }

            return [
                'success' => true,
                'message' => 'OK'
            ];
        } catch (\Exception $e) {
            Log::channel('erp')
                ->error('Ошибка при проверке максимального количества заказов у пользователя ' .
                    $user->id . ': ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Ошибка при проверки максимального количества заказов: ' . $e->getMessage()
            ];
        }
    }

    private static function hasMaterialsInWorkshop($marketplaceOrderItem): bool
    {
        $marketplaceItem = $marketplaceOrderItem->item()->first();
        $materialConsumptions = $marketplaceItem->consumption;

        if ($materialConsumptions->isEmpty()) {
            $text = 'Для заказа #' . $marketplaceOrderItem->id . ' не указаны материалы!';
            TgService::sendMessage(config('telegram.admin_id'), $text);

            return false;
        }

        $quantityOrderItem = $marketplaceOrderItem->quantity;

        foreach ($materialConsumptions as $materialConsumption) {
            $materialId = $materialConsumption->material_id;
            $materialConsumptionQuantity = $materialConsumption->quantity;

            $materialInWorkhouse = InventoryService::materialInWorkshop($materialId);

            if ($materialInWorkhouse < $materialConsumptionQuantity * $quantityOrderItem) {
                return false;
            }
        }

        return true;
    }

    private static function assignOrderToUser($marketplaceOrderItem): array
    {
        try {
            $marketplaceItem = $marketplaceOrderItem->item()->first();
            $materialConsumptions = $marketplaceItem->consumption;
            $quantityOrderItem = $marketplaceOrderItem->quantity;

            $field = match (auth()->user()->role->name) {
                'seamstress' => 'seamstress_id',
                'cutter'     => 'cutter_id',
                default      => throw new \Exception('Недопустимая роль: ' . auth()->user()->role->name),
            };

            $status = match (auth()->user()->role->name) {
                'seamstress' => 4,
                'cutter'     => 7,
                default      => throw new \Exception('Недопустимая роль: ' . auth()->user()->role->name),
            };

            DB::beginTransaction();

            $marketplaceOrderItem->update([
                'status' => $status,
                $field => auth()->user()->id
            ]);

            $order = Order::query()->create([
                'type_movement' => 3,
                'status' => 4,
                $field => auth()->user()->id,
                'comment' => 'По заказу No: ' . $marketplaceOrderItem->marketplaceOrder->order_id,
                'marketplace_order_id' => $marketplaceOrderItem->marketplaceOrder->id
            ]);

            foreach ($materialConsumptions as $item) {
                $movementMaterial = new MovementMaterial();

                switch (auth()->user()->role->name) {
                    case 'cutter':
                        if ($item->material->type_id == 1) {
                            $movementMaterial->material_id = $item->material_id;
                            $movementMaterial->quantity = $item->quantity * $quantityOrderItem;
                            $movementMaterial->order_id = $order->id;
                            $movementMaterial->save();
                        }
                        break;
                    case 'seamstress':
                        if ($marketplaceOrderItem->cutter_id) {
                            if ($item->material->type_id != 1) {
                                $movementMaterial->material_id = $item->material_id;
                                $movementMaterial->quantity = $item->quantity * $quantityOrderItem;
                                $movementMaterial->order_id = $order->id;
                                $movementMaterial->save();
                            }
                        } else {
                            $movementMaterial->material_id = $item->material_id;
                            $movementMaterial->quantity = $item->quantity * $quantityOrderItem;
                            $movementMaterial->order_id = $order->id;
                            $movementMaterial->save();
                        }
                        break;
                }
            }

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();

            self::restoreReserve($marketplaceOrderItem);

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

    /**
     * @throws Exception
     */
    public static function getNewOrderItem_OLD(): array
    {
        $result = self::checkSchedule();
        if (!$result['success']) {
            return $result;
        }

        $result = self::checkMaxStack(auth()->user());
        if (!$result['success']) {
            return $result;
        }

        foreach (self::getFilteredItems() as $marketplaceOrderItem) {
            $item = $marketplaceOrderItem->item()->first();

            if (!self::hasMaterialsInWorkshop($marketplaceOrderItem)) {
                $text = 'На товар ' . $item->title . ' '. $item->width . 'x' . $item->height . ' недостаточно материала на складе';
                TgService::sendMessage(config('telegram.admin_id'), $text);
                continue;
            }

            if(!self::canUseMaterial($marketplaceOrderItem)) {
                continue;
            }

            if (self::isReserved($marketplaceOrderItem)) {
                continue;
            }

            self::reserve($marketplaceOrderItem);

            $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceOrderItem->marketplaceOrder->marketplace_id);

            $text = 'Товар ' . $marketplaceName . ' #' . $marketplaceOrderItem->id .
                ' (' . $item->title . ' '. $item->width . 'x' . $item->height .
                ') взял в работу сотрудник: ' . auth()->user()->name;

            SendTelegramMessageJob::dispatch(config('telegram.admin_id'), $text);

            if (auth()->user()->tg_id) {
                SendTelegramMessageJob::dispatch(
                    auth()->user()->tg_id,
                    'Вы взяли в работу заказ # '
                    . $marketplaceOrderItem->marketplaceOrder->order_id . ' (' . $marketplaceName . '): '
                    . $item->title . ' ' . $item->width . 'x' . $item->height
                );
            }

            Log::channel('erp')->info($text);

            return self::assignOrderToUser($marketplaceOrderItem);
        }

        return [
            'success' => false,
            'message' => 'Нет доступных заказов'
        ];
    }

    public static function getNewOrderItem(): array
    {
        $user = auth()->user();

        if (!self::checkSchedule()['success']) {
            return self::checkSchedule();
        }

        if (!self::checkMaxStack($user)['success']) {
            return self::checkMaxStack($user);
        }

        if (!self::checkCutterDailyLimit($user)['success']) {
            return self::checkCutterDailyLimit($user);
        }

        return self::processAvailableItems();
    }

    protected static function processAvailableItems(): array
    {
        foreach (self::getFilteredItems() as $marketplaceOrderItem) {
            Log::channel('erp')
                ->info(
                    'Проверяем возможность взятия заказа №' . $marketplaceOrderItem->id .
                    ' сотрудником ' . auth()->user()->name
                );

            $result = self::tryProcessItem($marketplaceOrderItem);
            if ($result['success']) {
                return $result;
            }
        }

        return [
            'success' => false,
            'message' => 'Нет доступных заказов'
        ];
    }

    protected static function tryProcessItem($marketplaceOrderItem): array
    {
        $item = $marketplaceOrderItem->item()->first();

        if (!self::hasMaterialsInWorkshop($marketplaceOrderItem)) {
            self::notifyNoMaterials($item);
            return ['success' => false];
        }

        if (!self::canUseMaterial($marketplaceOrderItem)) {
            return ['success' => false];
        }

        if (self::isReserved($marketplaceOrderItem)) {
            return ['success' => false];
        }

        self::reserve($marketplaceOrderItem);
        self::notifyAboutReservation($marketplaceOrderItem, $item);

        return self::assignOrderToUser($marketplaceOrderItem);
    }

    protected static function notifyNoMaterials($item): void
    {
        $text = sprintf(
            'На товар %s %sx%s недостаточно материала на складе',
            $item->title,
            $item->width,
            $item->height
        );

        TgService::sendMessage(config('telegram.admin_id'), $text);
    }

    protected static function notifyAboutReservation($marketplaceOrderItem, $item): void
    {
        $marketplaceName = MarketplaceOrderService::getMarketplaceName(
            $marketplaceOrderItem->marketplaceOrder->marketplace_id
        );

        $text = sprintf(
            'Товар %s #%d (%s %sx%s) взял в работу сотрудник: %s',
            $marketplaceName,
            $marketplaceOrderItem->id,
            $item->title,
            $item->width,
            $item->height,
            auth()->user()->name
        );

        SendTelegramMessageJob::dispatch(config('telegram.admin_id'), $text);

        if (auth()->user()->tg_id) {
            SendTelegramMessageJob::dispatch(
                auth()->user()->tg_id,
                sprintf(
                    'Вы взяли в работу заказ # %s (%s): %s %sx%s',
                    $marketplaceOrderItem->marketplaceOrder->order_id,
                    $marketplaceName,
                    $item->title,
                    $item->width,
                    $item->height
                )
            );
        }

        Log::channel('erp')->info($text);
    }

    private static function getFilteredItems(): Collection
    {
        $items = MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_items', 'marketplace_order_items.marketplace_item_id', '=', 'marketplace_items.id');

//        если швея (без кроя), то заказы со статусом "раскроено"
        if ((auth()->user()->isSeamstress() && !auth()->user()->is_cutter)) {
            $items = $items->where('marketplace_order_items.status', 8);
        }

//          если закройщик или швея-закройщик, то заказы со статусом "новый"
        if ((auth()->user()->isSeamstress() && auth()->user()->is_cutter) || auth()->user()->isCutter()) {
            $items = $items->where('marketplace_order_items.status', 0);
        }

        $items = $items
            ->orderBy('marketplace_orders.fulfillment_type', 'asc');

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

        return $items
            ->orderBy('marketplace_orders.created_at', 'asc')
            ->orderBy('marketplace_order_items.id', 'asc')
            ->select('marketplace_order_items.*')
            ->get();
    }

    private static function canUseMaterial(MarketplaceOrderItem $marketplaceOrderItem): bool
    {
        $clothConsumptions = $marketplaceOrderItem->item->consumption
            ->filter(fn($consumption) => $consumption->material->type_id === 1);

        $allowedMaterialIds = auth()->user()->materials->pluck('id');

        if ($allowedMaterialIds->isEmpty()) {
            return true;
        }

        return $clothConsumptions->every(
            fn($consumption) => $allowedMaterialIds->contains($consumption->material_id)
        );
    }

    public static function createItem(Sku $sku, MarketplaceOrder $marketplaceOrder): void
    {
        MarketplaceOrderItem::query()->create([
            'marketplace_order_id' => $marketplaceOrder->id,
            'marketplace_item_id' => $sku->item_id,
            'quantity' => 1,
            'price' => 0,
            'created_at' => Carbon::parse($marketplaceOrder->created_at),
        ]);
    }

    public static function hasReadyItem(Sku $sku): bool
    {
        return MarketplaceOrderItem::query()
            ->where('marketplace_item_id', $sku->item_id)
            ->where('status', 11)
            ->exists();
    }

    public static function reserveReadyItem(Sku $sku, MarketplaceOrder $marketplaceOrder): void
    {
        $marketplaceOrderItem = MarketplaceOrderItem::query()
            ->where('marketplace_item_id', $sku->item_id)
            ->where('status', 11)
            ->first();

        self::saveOrderToHistory($marketplaceOrderItem);

        $marketplaceOrderItem->marketplace_order_id = $marketplaceOrder->id;
        $marketplaceOrderItem->status = 13; // в сборке
        $marketplaceOrderItem->save();

        Log::channel('erp')
            ->info('Зарезервировали заказ ' . $marketplaceOrder->id . ' с товаром ' . $marketplaceOrderItem->id);
    }

    public static function saveOrderToHistory(MarketplaceOrderItem $marketplaceOrderItem): void
    {
        MarketplaceOrderHistory::firstOrCreate([
            'marketplace_order_id' => $marketplaceOrderItem->marketplace_order_id,
            'marketplace_order_item_id' => $marketplaceOrderItem->id,
            'status' => 'returned',
        ]);

        Log::channel('erp')
            ->info('Заказ ' . $marketplaceOrderItem->marketplaceOrder->order_id . ' сохранен в историю с товаром '
                . $marketplaceOrderItem->id . ' (значит этот заказ отмененный, раз товар на хранении)');

        $marketplaceOrderItem->marketplaceOrder->status = 9; // возврат
        $marketplaceOrderItem->marketplaceOrder->save();
    }

    public static function restoreOrderFromHistory(MarketplaceOrderItem $selectedItem): void
    {
        try {
            DB::beginTransaction();

            $marketplaceOrderHistory = $selectedItem->history()
                ->orderByDesc('created_at')
                ->first();

            if (!$marketplaceOrderHistory) {
                Log::channel('erp')
                    ->error('В Истории нет заказа ' . $selectedItem->marketplace_order_id . ' по товару ' . $selectedItem->id);
                DB::rollBack();
                return;
            }

            Log::channel('erp')
                ->info('Для товара id ' . $selectedItem->id . 'Восстановлен заказ #' . $marketplaceOrderHistory->marketplace_order_id .
                    ' вместо текущего заказа #' . $selectedItem->marketplace_order_id);

            $selectedItem->marketplace_order_id = $marketplaceOrderHistory->marketplace_order_id;
            $selectedItem->status = 11; // на хранении
            $selectedItem->save();

            $marketplaceOrderHistory->delete();

            DB::commit(); // фиксируем изменения
        } catch (Exception $e) {
            DB::rollBack();

            Log::channel('erp')->error('Ошибка при восстановлении заказа из истории: ' . $e->getMessage(), [
                'marketplace_order_item_id' => $selectedItem->id,
                'marketplace_order_id' => $selectedItem->marketplace_order_id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private static function isReserved($marketplaceOrderItem): bool
    {
        if ($marketplaceOrderItem->status == 99) {
            Log::channel('erp')
                ->warning('Конкурентный доступ! Заказ ' . $marketplaceOrderItem->id . ' находится в резерве');
            return true;
        }

        return false;
    }

    private static function reserve($marketplaceOrderItem): void
    {
        $marketplaceOrderItem->status = 99;
        $marketplaceOrderItem->save();

        Log::channel('erp')
            ->warning('Зарезервирован товар ' . $marketplaceOrderItem->id);
    }

    private static function restoreReserve($marketplaceOrderItem): void
    {
        $marketplaceOrderItem->status = ($marketplaceOrderItem->cutter_id) ? 8 : 0;
        $marketplaceOrderItem->save();

        Log::channel('erp')
            ->warning('Восстановлен резервированный товара ' . $marketplaceOrderItem->id);
    }

    private static function checkCutterDailyLimit(User $user): array
    {
        if ($user->isCutter()) {
            $inCutting = self::getCutterMetersByStatus(7);
            $inCut = self::getCutterMetersByStatus(8);

            $meters = ($inCutting + $inCut) / 100;
            $dailyLimit = Setting::getValue('cutter_daily_limit');

            if ($meters >= $dailyLimit) {
                return [
                    'success' => false,
                    'message' => 'Вы не можете взять больше заказов! Ваш метраж (готовый и в работе): ' .
                        $meters . ', при лимите в ' . $dailyLimit
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'OK'
        ];
    }

    private static function getCutterMetersByStatus(int $status): float
    {
        return MarketplaceOrderItem::query()
            ->where('status', $status)
            ->where('cutter_id', auth()->id())
            ->when($status === 8, fn($q) => $q->whereDate('cutting_completed_at', now()->toDateString()))
            ->whereHas('item')
            ->with('item')
            ->get()
            ->sum(fn($orderItem) => $orderItem->item?->width ?? 0);
    }

    public function getOrdersGroupedByMaterial(): \Illuminate\Support\Collection
    {
        $items = MarketplaceOrderItem::query()
            ->with('marketplaceOrder')
            ->with('item')
            ->where('marketplace_order_items.status', 7)
            ->where('marketplace_order_items.cutter_id', auth()->user()->id)
            ->get();

        return collect($items)
            ->groupBy(fn($item) => $item->item->title ?? '-----')
            ->map(function ($group) {
                return $group->sortBy(function ($item) {
                    return [$item->item->width, $item->item->height];
                });
            });
    }
}
