<?php

namespace App\Services;

use App\Jobs\SendMaxMessageJob;
use App\Jobs\SendTelegramMessageJob;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderHistory;
use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Sku;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketplaceOrderItemService
{
    public static function getFiltered($request): Builder
    {
        $status = match (auth()->user()->role->name) {
            'otk' => $request->status ?? 'cut',
            'cutter' => $request->status ?? 'cutting',
            'seamstress' => $request->status ?? 'in_work',
            default => $request->status ?? 'new',
        };

        $items = MarketplaceOrderItem::query();

        $items = $items->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_items', 'marketplace_order_items.marketplace_item_id', '=', 'marketplace_items.id')
            ->orderBy('marketplace_orders.fulfillment_type', 'asc')
            ->orderBy('marketplace_orders.created_at', 'asc')
            ->orderBy('marketplace_order_items.id', 'asc')
            ->select('marketplace_order_items.*');

        if ($request->has('search') && (auth()->user()->isAdmin() || auth()->user()->isStorekeeper())) {
            if (mb_strlen(trim($request->search)) == 15) {
                $request->search = MarketplaceApiService::getOzonPostingNumberByBarcode($request->search);
            }
            $items = $items
                ->where('marketplace_orders.order_id', 'like', '%'.$request->search.'%')
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

        if (auth()->user()->isSeamstress()) {
            $seamstressWorkshopId = auth()->user()->currentWorkshop()?->id;
            if ($seamstressWorkshopId) {
                $items = $items->where('marketplace_order_items.workshop_id', $seamstressWorkshopId);
            }
            if ($status != 'new') {
                $items = $items->where('marketplace_order_items.seamstress_id', auth()->user()->id);
            }
        }

        if (auth()->user()->isCutter()) {
            $cutterWorkshopId = auth()->user()->currentWorkshop()?->id;
            if ($cutterWorkshopId) {
                $items = $items->where('marketplace_order_items.workshop_id', $cutterWorkshopId);
            }
            if ($status != 'new') {
                $items = $items->where('marketplace_order_items.cutter_id', auth()->user()->id);
            }
        }

        if (auth()->user()->isOtk()) {
            $otkWorkshopId = auth()->user()->currentWorkshop()?->id;
            if ($otkWorkshopId) {
                $items = $items->where('marketplace_order_items.workshop_id', $otkWorkshopId);
            }
        }

        if ($request->has('user_id') && $status != 'new') {
            $items = $items->where(function ($query) use ($request) {
                $query->where('marketplace_order_items.seamstress_id', $request->user_id)
                    ->orWhere('marketplace_order_items.cutter_id', $request->user_id)
                    ->orWhere('marketplace_order_items.otk_id', $request->user_id);
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

        if ($request->has('width')) {
            $items = $items->where('marketplace_items.width', $request->width);
        }

        if ($request->has('height')) {
            $items = $items->where('marketplace_items.height', $request->height);
        }

        if ($request->has('material')) {
            $items = $items->where('marketplace_items.title', $request->material);
        }

        if ($request->has('fulfillment_type') && $request->fulfillment_type !== '') {
            $items = $items->where('marketplace_orders.fulfillment_type', $request->fulfillment_type);
        }

        // Фильтр по цеху (опционально, для всех ролей)
        if ($request->has('workshop_id') && $request->workshop_id) {
            $items = $items->where('marketplace_order_items.workshop_id', $request->workshop_id);
        }

        return $items;
    }

    public static function cancelToSeamstress(MarketplaceOrderItem $marketplaceOrderItem): array
    {
        $status = $marketplaceOrderItem->status;

        if (! in_array($status, [4, 5, 7])) {
            return [
                'success' => false,
                'message' => 'Заказ с таким статусом не может быть отменен',
            ];
        }

        try {
            // FIXME: рассмотреть разбиение длинных транзакций на более мелкие атомарные операции
            DB::beginTransaction();

            $logMessage = '';

            //  если на раскрое
            if ($status == 7) {
                $logMessage =
                    'Отменен закрой заказа № '.$marketplaceOrderItem->marketplaceOrder->order_id.
                    ' (товар #'.$marketplaceOrderItem->id.'). Холдирование материалов на закрой - удалено.'.PHP_EOL.
                    'Закройщик: '.$marketplaceOrderItem->cutter->name.
                    ' ('.$marketplaceOrderItem->cutter->id.')'.PHP_EOL.
                    'Инициатор: '.auth()->user()->name.' ('.auth()->user()->id.')'.PHP_EOL;

                StackService::reduceStack($marketplaceOrderItem->cutter_id);

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

            //  если на пошиве или стикеровке
            if ($status == 4 || $status == 5) {
                $logMessage =
                    'Отменен пошив заказа № '.$marketplaceOrderItem->marketplaceOrder->order_id.
                    ' (товар #'.$marketplaceOrderItem->id.'). Холдирование материалов на пошив - удалено. Не выплаченная зарплата и бонусы - удалены.'.PHP_EOL.
                    'Швея: '.$marketplaceOrderItem->seamstress->name.
                    ' ('.$marketplaceOrderItem->seamstress->id.')'.PHP_EOL.
                    'Инициатор: '.auth()->user()->name.' ('.auth()->user()->id.')'.PHP_EOL;

                $marketplaceOrderItem->status = ($marketplaceOrderItem->cutter_id) ? 8 : 0;
                $marketplaceOrderItem->seamstress_id = 0;
                $marketplaceOrderItem->completed_at = null;
                $marketplaceOrderItem->save();

                $marketplaceOrder = $marketplaceOrderItem->marketplaceOrder;

                if ($status == 4 && ! $marketplaceOrderItem->cutter_id) {
                    $marketplaceOrder->status = 0;
                    $marketplaceOrder->save();
                }

                $order = Order::query()
                    ->where('marketplace_order_id', $marketplaceOrder->id);

                if ($marketplaceOrderItem->cutter_id) {
                    $order = $order->whereNull('cutter_id');
                }

                $order = $order->first();

                MovementMaterial::query()
                    ->where('order_id', $order->id)
                    ->delete();

                $order->delete();
            }

            //  если отменяет заказ не админ, то начислить штраф.
            if (! auth()->user()->isAdmin()) {
                TransactionService::penalizeUserForOrderCancellation($marketplaceOrderItem);
            }

            Log::channel('items')
                ->notice($logMessage);

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error($e->getMessage());

            Log::channel('orders')
                ->error('Заказ № '.$marketplaceOrderItem->marketplaceOrder->order_id.' не удалось отменить!');

            return [
                'success' => false,
                'message' => 'Внутренняя ошибка',
            ];
        }

        return [
            'success' => true,
            'message' => 'Заказ отменен',
        ];
    }

    /**
     * Количество товаров в пошиве (статус 4) с опциональной фильтрацией по цеху.
     */
    public static function toWork(?int $workshopId = null): int
    {
        $marketplaceOrderItemInWork = MarketplaceOrderItem::query()
            ->where('status', 4);

        if (auth()->user()->isSeamstress()) {
            $marketplaceOrderItemInWork = $marketplaceOrderItemInWork
                ->where('seamstress_id', auth()->id());
        }

        return $marketplaceOrderItemInWork
            ->when($workshopId, fn ($q) => $q->where('workshop_id', $workshopId))
            ->sum('quantity');
    }

    /**
     * Количество товаров в закрое (статус 7) с опциональной фильтрацией по цеху.
     */
    public static function toCutting(?int $workshopId = null): int
    {
        $marketplaceOrderItemInWork = MarketplaceOrderItem::query()
            ->where('status', 7);

        if (auth()->user()->isCutter()) {
            $marketplaceOrderItemInWork = $marketplaceOrderItemInWork
                ->where('cutter_id', auth()->id());
        }

        return $marketplaceOrderItemInWork
            ->when($workshopId, fn ($q) => $q->where('workshop_id', $workshopId))
            ->sum('quantity');
    }

    /**
     * Количество новых заданий (статус 0) с опциональной фильтрацией по цеху.
     */
    public static function new(?int $workshopId = null): int
    {
        return MarketplaceOrderItem::query()
            ->where('status', 0)
            ->when($workshopId, fn ($q) => $q->where('workshop_id', $workshopId))
            ->sum('quantity');
    }

    /**
     * Количество раскроенных товаров (статус 8) с опциональной фильтрацией по цеху.
     */
    public static function cut(?int $workshopId = null): int
    {
        return MarketplaceOrderItem::query()
            ->where('status', 8)
            ->when($workshopId, fn ($q) => $q->where('workshop_id', $workshopId))
            ->sum('quantity');
    }

    /**
     * Количество срочных заказов FBS (статусы 0, 4) с опциональной фильтрацией по цеху.
     */
    public static function urgent(?int $workshopId = null): int
    {
        return MarketplaceOrderItem::query()
            ->join('marketplace_orders',
                'marketplace_orders.id',
                '=',
                'marketplace_order_items.marketplace_order_id'
            )
            ->whereIn('marketplace_order_items.status', [0, 4])
            ->where('marketplace_orders.fulfillment_type', 'FBS')
            ->when($workshopId, fn ($q) => $q->where('marketplace_order_items.workshop_id', $workshopId))
            ->sum('quantity');
    }

    /**
     * Количество товаров на стикеровке (статус 5), сгруппированных по кластеру заказа.
     *
     * @return array<string, int> cluster => count
     */
    public static function stickedByCluster(?int $workshopId = null): array
    {
        return MarketplaceOrderItem::query()
            ->join('marketplace_orders',
                'marketplace_orders.id',
                '=',
                'marketplace_order_items.marketplace_order_id')
            ->where('marketplace_order_items.status', 5)
            ->whereNotNull('marketplace_orders.cluster')
            ->when($workshopId, fn ($q) => $q->where('marketplace_order_items.workshop_id', $workshopId))
            ->groupBy('marketplace_orders.cluster')
            ->selectRaw('marketplace_orders.cluster as cluster, COUNT(*) as total')
            ->pluck('total', 'cluster')
            ->toArray();
    }

    public static function getSeamstressesLargeSizeRatingOLD(array $dates): array
    {
        $seamstressesLargeSizeRating = [];
        $seamstresses = User::query()
            ->where('role_id', '1')
            ->where('name', 'not like', '%Тест%')
            ->get();

        foreach ($seamstresses as $seamstress) {
            $seamstressesLargeSizeRating[$seamstress->id]['name'] = $seamstress->short_name;
            foreach ($dates as $date) {
                $startDate = $endDate = $date;

                $seamstressesLargeSizeRating[$seamstress->id][$date] = self::getRatingByDate($seamstress, $startDate, $endDate);
            }
        }

        return $seamstressesLargeSizeRating;
    }

    public static function getSeamstressesLargeSizeRating(array $dates): array
    {
        if (empty($dates)) {
            return [];
        }

        $startDate = Carbon::parse($dates[0])->startOfDay();
        $endDate = Carbon::parse(end($dates))->endOfDay();

        // Один запрос для получения всех данных
        $ratings = MarketplaceOrderItem::query()
            ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
            ->whereIn('marketplace_order_items.seamstress_id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('role_id', '1')
                    ->where('name', 'not like', '%Тест%');
            })
            ->where('marketplace_order_items.status', 3)
            ->whereBetween('marketplace_order_items.completed_at', [$startDate, $endDate])
            ->selectRaw('
              marketplace_order_items.seamstress_id,
              DATE(marketplace_order_items.completed_at) as date,
              SUM(marketplace_order_items.quantity * marketplace_items.width / 100) as total_volume,
              SUM(marketplace_order_items.quantity) as total_quantity
              ')
            ->groupBy('marketplace_order_items.seamstress_id', 'date')
            ->get()
            ->keyBy(function ($item) {
                return $item->seamstress_id.'_'.$item->date;
            });

        // Формируем результат из полученных данных
        $seamstresses = User::query()
            ->where('role_id', '1')
            ->where('name', 'not like', '%Тест%')
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($seamstresses as $seamstress) {
            $result[$seamstress->id]['name'] = $seamstress->short_name;
            foreach ($dates as $date) {
                $key = $seamstress->id.'_'.$date;
                $rating = $ratings->get($key);

                if ($rating && $rating->total_quantity > 0) {
                    $result[$seamstress->id][$date] = round($rating->total_volume / $rating->total_quantity, 1);
                } else {
                    $result[$seamstress->id][$date] = '0.0';
                }
            }
        }

        return $result;
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
            ->whereBetween('marketplace_order_items.completed_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->selectRaw('SUM(marketplace_order_items.quantity * marketplace_items.width / 100) as total_volume, SUM(marketplace_order_items.quantity) as total_quantity')
            ->first()?->getAttributes() ?? [];

        $totalQuantity = $seamstressRating['total_quantity'] ?? 0;
        $totalVolume = $seamstressRating['total_volume'] ?? 0;

        return $totalQuantity > 0
            ? round($totalVolume / $totalQuantity, 1)
            : '0.0';
    }

    public static function getRatingOLD(): Collection|\Illuminate\Support\Collection
    {
        return User::query()
            ->whereIn('role_id', [1, 4])
            ->where('name', 'not like', '%Тест%')
            ->get()
            ->map(function (User $user) {
                $startDate = Carbon::now()->subDays(14)->toDateString();
                $startDate2 = Carbon::now()->subMonth()->toDateString();
                $endDate = Carbon::now()->toDateString();

                $user->setAttribute('ratingNow', MarketplaceOrderItemService::getRatingByDate($user, $endDate, $endDate));
                $user->setAttribute('rating2week', MarketplaceOrderItemService::getRatingByDate($user, $startDate, $endDate));
                $user->setAttribute('rating1month', MarketplaceOrderItemService::getRatingByDate($user, $startDate2, $endDate));

                return $user;
            });
    }

    public static function getRating(): Collection|\Illuminate\Support\Collection
    {
        $today = Carbon::now()->toDateString();
        $twoWeeksAgo = Carbon::now()->subDays(14)->toDateString();
        $oneMonthAgo = Carbon::now()->subMonth()->toDateString();

        $userIds = User::query()
            ->whereIn('role_id', [1, 4])
            ->where('name', 'not like', '%Тест%')
            ->pluck('id');

        // Один запрос для всех рейтингов за сегодня
        $ratingNow = MarketplaceOrderItem::query()
            ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
            ->whereIn('marketplace_order_items.seamstress_id', $userIds)
            ->where('marketplace_order_items.status', 3)
            ->whereDate('marketplace_order_items.completed_at', $today)
            ->selectRaw('
              marketplace_order_items.seamstress_id,
              SUM(marketplace_order_items.quantity * marketplace_items.width / 100) as total_volume,
              SUM(marketplace_order_items.quantity) as total_quantity
          ')
            ->groupBy('marketplace_order_items.seamstress_id')
            ->pluck('total_volume', 'seamstress_id');

        // Один запрос за 2 недели
        $rating2Week = MarketplaceOrderItem::query()
            ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
            ->whereIn('marketplace_order_items.seamstress_id', $userIds)
            ->where('marketplace_order_items.status', 3)
            ->whereBetween('marketplace_order_items.completed_at', [$twoWeeksAgo.' 00:00:00', $today.' 23:59:59'])
            ->selectRaw('
              marketplace_order_items.seamstress_id,
              SUM(marketplace_order_items.quantity * marketplace_items.width / 100) as total_volume,
              SUM(marketplace_order_items.quantity) as total_quantity
          ')
            ->groupBy('marketplace_order_items.seamstress_id')
            ->get()
            ->keyBy('seamstress_id');

        // Один запрос за месяц
        $rating1Month = MarketplaceOrderItem::query()
            ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
            ->whereIn('marketplace_order_items.seamstress_id', $userIds)
            ->where('marketplace_order_items.status', 3)
            ->whereBetween('marketplace_order_items.completed_at', [$oneMonthAgo.' 00:00:00', $today.' 23:59:59'])
            ->selectRaw('
              marketplace_order_items.seamstress_id,
              SUM(marketplace_order_items.quantity * marketplace_items.width / 100) as total_volume,
              SUM(marketplace_order_items.quantity) as total_quantity
          ')
            ->groupBy('marketplace_order_items.seamstress_id')
            ->get()
            ->keyBy('seamstress_id');

        return User::query()
            ->whereIn('role_id', [1, 4])
            ->where('name', 'not like', '%Тест%')
            ->get()
            ->map(function (User $user) use ($ratingNow, $rating2Week, $rating1Month) {
                // Рейтинг за сегодня
                $now = $ratingNow->get($user->id);
                $user->setAttribute('ratingNow', $now ? round($now, 1) : '0.0');

                // Рейтинг за 2 недели
                $data2Week = $rating2Week->get($user->id);
                if ($data2Week && $data2Week->total_quantity > 0) {
                    $user->setAttribute('rating2week', round($data2Week->total_volume / $data2Week->total_quantity,
                        1));
                } else {
                    $user->setAttribute('rating2week', '0.0');
                }

                // Рейтинг за месяц
                $data1Month = $rating1Month->get($user->id);
                if ($data1Month && $data1Month->total_quantity > 0) {
                    $user->setAttribute('rating1month', round($data1Month->total_volume / $data1Month->total_quantity,
                        1));
                } else {
                    $user->setAttribute('rating1month', '0.0');
                }

                return $user;
            });
    }

    /**
     * Получить товары для стикеровки с фильтрацией по цеху киоска.
     */
    public static function getItemsForLabeling(Request $request, ?int $workshopId = null): Collection
    {
        $items = MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_items', 'marketplace_order_items.marketplace_item_id', '=', 'marketplace_items.id')
            ->select('marketplace_order_items.*')
            ->when($workshopId, fn ($q) => $q->where('marketplace_order_items.workshop_id', $workshopId));

        // Если отсканирован order_id — фильтруем только по нему
        if ($request->filled('scan_order_id')) {
            return $items
                ->where('marketplace_orders.order_id', $request->scan_order_id)
                ->whereIn('marketplace_order_items.status', [3, 5])
                ->get();
        }

        if ($request->has('marketplace_id')) {
            $items = $items->where('marketplace_orders.marketplace_id', $request->marketplace_id);
        }

        $items = $items
            ->where('marketplace_order_items.status', '5')
            ->where('marketplace_items.title', $request->material ?? '')
            ->where('marketplace_items.height', $request->height ?? 0)
            ->where('marketplace_items.width', $request->width ?? 0);

        $user = User::find($request->user_id ?? 0);
        if ($user && ($user->isOtk() || $user->isAdmin())) {
            $items = $items
                ->where('marketplace_order_items.seamstress_id', $request->seamstress_id ?? 0);
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

        if (! $field) {
            return 0;
        }

        return Setting::query()->where('name', $field)->first()->value;
    }

    private static function checkSchedule(): array
    {
        if (ScheduleService::isEnabledSchedule()) {
            if (! ScheduleService::isWorkDay()) {

                Log::channel('worker_limits')
                    ->error('Сотрудник '.auth()->user()->name.' пытался взять заказ в нерабочий день!');

                return [
                    'success' => false,
                    'message' => 'Вы не можете взять заказ в нерабочий день!',
                ];
            }

            if (! ScheduleService::hasWorkDayStarted()) {

                Log::channel('worker_limits')
                    ->error('Сотрудник '.auth()->user()->name.' пытался взять заказ в нерабочее время!');

                return [
                    'success' => false,
                    'message' => 'Вы не можете взять заказ в нерабочее время!',
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'OK',
        ];
    }

    /**
     * Проверка заполнения стэка заказов.
     */
    private static function checkMaxStack(User $user): array
    {
        try {
            $query = MarketplaceOrderItem::query();

            $orderItemsByUser = match ($user->role->name) {
                'seamstress' => $query->where('seamstress_id', $user->id)
                    ->whereIn('status', [4, 5]),
                'cutter' => $query->where('cutter_id', $user->id)
                    ->where('status', 7),
                default => throw new \Exception('Недопустимая роль: '.$user->role->name),
            };

            $maxCountOrderItems = self::getMaxQuantityOrdersToUserRole();

            if ($orderItemsByUser->count() >= $maxCountOrderItems) {

                Log::channel('worker_limits')
                    ->error('Сотрудник '.$user->name.', id: '.$user->id.
                        '. Пытался взять больше '.$maxCountOrderItems.', текущее количество в работе: '.$orderItemsByUser->count());

                return [
                    'success' => false,
                    'message' => 'Вы не можете взять больше '.$maxCountOrderItems.' заказов!',
                ];
            }

            if ($user->role->name === 'cutter') {
                $maxStack = StackService::getMaxStackByUser($user->id)->max;
                if ($maxStack >= $maxCountOrderItems) {

                    Log::channel('worker_limits')
                        ->error('СТЭК! Достигнут максимум заказов у закройщика '.$user->name.', id: '.$user->id.
                            '. Всего можно взять: '.$maxCountOrderItems.', текущее количество в работе (в стэке): '.$maxStack);

                    return [
                        'success' => false,
                        'message' => 'Достигнут максимум заказов. Сначала вам необходимо закрыть все текущие заказы.',
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'OK',
            ];
        } catch (\Exception $e) {
            Log::channel('worker_limits')
                ->error('Ошибка при проверке максимального количества заказов у пользователя '.
                    $user->id.': '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ошибка при проверки максимального количества заказов: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Проверка наличия материалов в работе.
     */
    private static function hasMaterialsInWorkshop($marketplaceOrderItem): bool
    {
        $marketplaceItem = $marketplaceOrderItem->item()->first();
        $materialConsumptions = $marketplaceItem->consumption;

        if ($materialConsumptions->isEmpty()) {
            $text = 'Для заказа #'.$marketplaceOrderItem->id.' не указаны материалы!';
            NotificationService::notifyAdmin($text);

            return false;
        }

        $user = auth()->user();
        $quantityOrderItem = $marketplaceOrderItem->quantity;

        $shift = $user->currentShift();
        $shiftUserIds = $shift ? $shift->getCurrentUsers()->pluck('id') : collect();

        foreach ($materialConsumptions as $materialConsumption) {
            if ($user->seamstressNotCut() && $materialConsumption->material->type_id === Material::TYPE_FABRIC) {
                continue;
            }

            // Аксессуары (тесьма и т.п.) пришивает швея; если сотрудник не швея — наличие не проверяем.
            if (! $user->isSeamstress() && $materialConsumption->material->type_id === Material::TYPE_ACCESSORY) {
                continue;
            }

            // Упаковка списывается отдельным потоком упаковщиком (KioskService / StickerPrintingController);
            // швеям и закройщикам она не нужна — пропускаем проверку её наличия.
            if ($materialConsumption->material->type_id === Material::TYPE_PACKAGING) {
                continue;
            }

            // Сумма текущих остатков рулонов смены в цехе
            // current_quantity = initial_quantity − уже использовано (MovementMaterial с roll_id)
            $rollsInShift = Roll::query()
                ->where('material_id', $materialConsumption->material_id)
                ->where('status', Roll::STATUS_IN_WORKSHOP)
                ->when($shift, fn ($q) => $q->where('shift_id', $shift->id))
                ->get();

            $availableInShift = $rollsInShift->sum('current_quantity');

            // Захолдировано: MovementMaterial без roll_id для пользователей этой смены
            $holdByShift = 0;
            if ($shift) {
                $holdByShift = MovementMaterial::query()
                    ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
                    ->where('movement_materials.material_id', $materialConsumption->material_id)
                    ->whereNull('movement_materials.roll_id')
                    ->where('orders.type_movement', 3)
                    ->where('orders.status', 4)
                    ->where(function ($q) use ($shiftUserIds) {
                        $q->whereIn('orders.seamstress_id', $shiftUserIds)
                            ->orWhereIn('orders.cutter_id', $shiftUserIds);
                    })
                    ->sum('movement_materials.quantity');
            }

            $materialInShift = $availableInShift - $holdByShift;
            $required = $materialConsumption->quantity * $quantityOrderItem;

            if ($materialInShift < $required) {
                self::logMaterialShortage(
                    $marketplaceOrderItem->id,
                    $materialConsumption,
                    $availableInShift,
                    $holdByShift,
                    $required,
                    $shift,
                    $rollsInShift->count(),
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Логирование нехватки конкретного материала в смене (диагностика причины отказа).
     *
     * @param  int  $orderItemId  ID позиции заказа (marketplace_order_items.id).
     * @param  MaterialConsumption  $materialConsumption  Запись расхода материала с загруженной связью material.
     * @param  float  $availableInShift  Доступный остаток рулонов текущей смены (сумма current_quantity).
     * @param  float  $holdByShift  Захолдированный объём по заказам смены без roll_id.
     * @param  float  $required  Требуемый объём: расход на единицу × количество в заказе.
     * @param  Shift|null  $shift  Текущая смена сотрудника.
     * @param  int  $rollsInShiftCount  Число рулонов материала, найденных в текущей смене.
     */
    private static function logMaterialShortage(
        int $orderItemId,
        MaterialConsumption $materialConsumption,
        float $availableInShift,
        float $holdByShift,
        float $required,
        ?Shift $shift,
        int $rollsInShiftCount,
    ): void {
        // Всего рулонов этого материала в цехе (без привязки к смене) — чтобы отличить
        // «рулон не найден в смене» (несовпадение shift_id) от «метража просто мало».
        $totalRolls = Roll::query()
            ->where('material_id', $materialConsumption->material_id)
            ->where('status', Roll::STATUS_IN_WORKSHOP)
            ->count();

        Log::channel('items')->warning(sprintf(
            'Заказ №%d: недостаточно материала «%s» — доступно %.2f (рулонов в смене %s: %d из %d в цехе), захолдировано %.2f, требуется %.2f',
            $orderItemId,
            $materialConsumption->material->title,
            $availableInShift,
            $shift ? ('#'.$shift->id) : '---',
            $rollsInShiftCount,
            $totalRolls,
            $holdByShift,
            $required,
        ));
    }

    /**
     * Назначение заказа сотруднику.
     */
    private static function assignOrderToUser($marketplaceOrderItem): array
    {
        try {
            $marketplaceItem = $marketplaceOrderItem->item()->first();
            $materialConsumptions = $marketplaceItem->consumption;
            $quantityOrderItem = $marketplaceOrderItem->quantity;

            $roleName = auth()->user()->role->name;

            $field = match ($roleName) {
                'seamstress' => 'seamstress_id',
                'cutter' => 'cutter_id',
                default => throw new \Exception('Недопустимая роль: '.$roleName),
            };

            $status = match ($roleName) {
                'seamstress' => 4,
                'cutter' => 7,
                default => throw new \Exception('Недопустимая роль: '.$roleName),
            };

            $statusFrom = ($field === 'cutter_id' || auth()->user()->is_cutter) ? 0 : 8; // 0 - новый, 8 - закроено

            // FIXME: рассмотреть разбиение длинных транзакций на более мелкие атомарные операции
            DB::beginTransaction();

            // Атомарный UPDATE с защитой от race condition
            $affected = DB::table('marketplace_order_items')
                ->where('id', $marketplaceOrderItem->id)
                ->where('status', $statusFrom)
                ->update([
                    'status' => $status,
                    $field => auth()->user()->id,
                    'workshop_id' => auth()->user()->currentWorkshop()?->id,
                ]);

            if ($affected === 0) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Заказ уже был взят другим сотрудником. Попробуйте ещё раз.',
                ];
            }

            if ($roleName == 'seamstress') {
                $marketplaceOrderItem->started_at = now();
                $marketplaceOrderItem->save();
            }

            if ($roleName == 'cutter') {
                StackService::incrementStackAndMaxStack(auth()->user()->id);
            }

            $marketplaceOrder = $marketplaceOrderItem->marketplaceOrder;
            $marketplaceOrder->status = 4;
            $marketplaceOrder->save();

            $order = Order::query()->create([
                'type_movement' => 3,
                'status' => 4,
                'shift_id' => auth()->user()->currentShift()?->id,
                'workshop_id' => auth()->user()->currentWorkshop()?->id,
                $field => auth()->user()->id,
                'comment' => 'По заказу No: '.$marketplaceOrderItem->marketplaceOrder->order_id,
                'marketplace_order_id' => $marketplaceOrderItem->marketplaceOrder->id,
            ]);

            foreach ($materialConsumptions as $item) {
                $movementMaterial = new MovementMaterial;

                switch ($roleName) {
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
                'message' => 'Внутренняя ошибка',
            ];
        }

        self::notifyAboutReservation($marketplaceOrderItem, $marketplaceItem);

        return [
            'success' => true,
            'message' => 'Заказ принят',
        ];
    }

    public static function getNewOrderItem(): array
    {
        $user = auth()->user();

        // Проверка расписания
        if (! self::checkSchedule()['success']) {
            return self::checkSchedule();
        }

        // Проверка стэка заказов
        if (! self::checkMaxStack($user)['success']) {
            return self::checkMaxStack($user);
        }

        // Проверка дневного лимита
        if (! self::checkDailyLimit($user)['success']) {
            return self::checkDailyLimit($user);
        }

        // Проверка таймаута (взять новый заказ сразу после старого)
        if (! self::checkTimeout($user)['success']) {
            return self::checkTimeout($user);
        }

        return self::processAvailableItems();
    }

    public static function fillEntireStack(): array
    {
        $user = auth()->user();

        if (! self::checkSchedule()['success']) {
            return self::checkSchedule();
        }

        $taken = 0;

        while (true) {
            $stackCheck = self::checkMaxStack($user);
            if (! $stackCheck['success']) {
                $lastError = $stackCheck['message'];
                break;
            }

            $limitCheck = self::checkDailyLimit($user);
            if (! $limitCheck['success']) {
                $lastError = $limitCheck['message'];
                break;
            }

            $result = self::processAvailableItems();
            if (! $result['success']) {
                $lastError = $result['message'];
                break;
            }

            $taken++;
        }

        if ($taken > 0) {
            return [
                'success' => true,
                'message' => "Получено заказов: {$taken}",
            ];
        }

        return [
            'success' => false,
            'message' => $lastError,
        ];
    }

    /**
     * Проверка возможности взятия заказов.
     */
    protected static function processAvailableItems(): array
    {
        /** @var MarketplaceOrderItem $marketplaceOrderItem */
        foreach (self::getFilteredItems() as $marketplaceOrderItem) {
            Log::channel('items')
                ->info(
                    'Проверяем возможность взятия заказа №'.$marketplaceOrderItem->id.
                    ' сотрудником '.auth()->user()->name
                );

            $result = self::tryProcessItem($marketplaceOrderItem);
            if ($result['success']) {
                return $result;
            }
        }

        Log::channel('items')
            ->info(
                'Для сотрудника '.auth()->user()->name.' нет доступных заказов'
            );

        return [
            'success' => false,
            'message' => 'Нет доступных заказов',
        ];
    }

    /**
     * Пытаемся взять заказ.
     */
    protected static function tryProcessItem($marketplaceOrderItem): array
    {
        $item = $marketplaceOrderItem->item()->first();
        $user = auth()->user();

        if (! self::hasMaterialsInWorkshop($marketplaceOrderItem)) {
            self::notifyNoMaterials($item);

            Log::channel('items')->warning(
                'Заказ №'.$marketplaceOrderItem->id.' отклонён: недостаточно материала в цехе (сотрудник '.$user->name.')'
            );

            return [
                'success' => false,
                'message' => 'Недостаточно материала в цехе',
            ];
        }

        if (! self::canUseMaterial($marketplaceOrderItem)) {
            Log::channel('items')->warning(
                'Заказ №'.$marketplaceOrderItem->id.' отклонён: ткань товара не входит в разрешённый список сотрудника '.$user->name
            );

            return [
                'success' => false,
                'message' => 'Ткань этого товара не входит в ваш разрешённый список',
            ];
        }

        //        if (self::isReserved($marketplaceOrderItem)) {
        //            return ['success' => false];
        //        }
        //
        //        self::reserve($marketplaceOrderItem);

        return self::assignOrderToUser($marketplaceOrderItem);
    }

    private const NO_MATERIAL_CACHE_PREFIX = 'no_material:item:';

    private const NO_MATERIAL_TTL = 1800;

    /**
     * Отправка уведомления о недостатке материала в цехе с cooldown'ом по товару.
     *
     * Чтобы десятки нажатий «Получить новый заказ» по одному и тому же товару
     * не плодили одинаковые уведомления админу, отправка конкретного товара
     * блокируется на NO_MATERIAL_TTL секунд (30 мин) через Cache-флаг.
     *
     * @param  object  $item  Модель товара (Item), для которого не хватает материала
     */
    protected static function notifyNoMaterials($item): void
    {
        $cacheKey = self::NO_MATERIAL_CACHE_PREFIX.$item->id;

        if (Cache::has($cacheKey)) {
            Log::channel('items')
                ->debug('Отправка уведомления о нехватке материала пропущено (недавно отправляли): товар #'.$item->id);

            return;
        }

        $text = sprintf(
            'На товар %s %sx%s недостаточно материала на складе',
            $item->title,
            $item->width,
            $item->height
        );

        NotificationService::notifyAdmin($text);

        Cache::put($cacheKey, true, self::NO_MATERIAL_TTL);
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
        SendMaxMessageJob::dispatch(config('services.max.admin_id'), $text);

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
        if (auth()->user()->max_id) {
            SendMaxMessageJob::dispatch(
                auth()->user()->max_id,
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

        Log::channel('items')->info($text);
    }

    private static function getFilteredItems(): Collection
    {
        $items = MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_items', 'marketplace_order_items.marketplace_item_id', '=', 'marketplace_items.id');

        //  если швея (без кроя), то заказы со статусом "раскроено"
        if ((auth()->user()->isSeamstress() && ! auth()->user()->is_cutter)) {
            $items = $items->where('marketplace_order_items.status', 8);
        }

        //  если закройщик или швея-закройщик, то заказы со статусом "новый"
        if ((auth()->user()->isSeamstress() && auth()->user()->is_cutter) || auth()->user()->isCutter()) {
            $items = $items->where('marketplace_order_items.status', 0);
        }

        $allowedMaterialIds = auth()->user()->materials->pluck('id');

        if ($allowedMaterialIds->isNotEmpty()) {
            $items = $items->whereHas('item', function ($q) use ($allowedMaterialIds) {
                $q->whereHas('consumption', function ($q2) use ($allowedMaterialIds) {
                    $q2->whereIn('material_id', $allowedMaterialIds)
                        ->whereHas('material', function ($q3) {
                            $q3->where('type_id', 1);  // только ткани
                        });
                });
            });
        }

        // Фильтр по цеху: выбираем только товары, разрешённые в цехе сотрудника
        $workshopId = auth()->user()->currentWorkshop()?->id;
        if ($workshopId) {
            $items = $items->whereExists(function ($q) use ($workshopId) {
                $q->select(DB::raw(1))
                    ->from('item_workshop')
                    ->whereColumn('item_workshop.marketplace_item_id', 'marketplace_order_items.marketplace_item_id')
                    ->where('item_workshop.workshop_id', $workshopId);
            });

            // Только товары, принадлежащие цеху сотрудника (или без цеха — старые данные)
            $items = $items->where(function ($q) use ($workshopId) {
                $q->where('marketplace_order_items.workshop_id', $workshopId)
                    ->orWhereNull('marketplace_order_items.workshop_id');
            });
        }

        $items = $items->with(['item.consumption.material']);

        // Приоритет FBO-кластера (цеховая/глобальная настройка) — "в первую очередь".
        // value = "<marketplace_id>|<cluster>". Заказы совпадающего кластера идут вверх.
        $clusterPriority = Setting::getValue('orders_cluster_priority', $workshopId);

        if ($clusterPriority !== null && $clusterPriority !== '' && str_contains($clusterPriority, '|')) {
            [$clusterMpId, $clusterName] = explode('|', $clusterPriority, 2);
            $items = $items->orderByRaw(
                'CASE WHEN marketplace_orders.marketplace_id = ? AND marketplace_orders.cluster = ? THEN 0 ELSE 1 END',
                [(int) $clusterMpId, $clusterName]
            );
        }

        $items = $items
            ->orderBy('marketplace_orders.fulfillment_type', 'asc');

        // Фильтр по типу исполнения — цеховая настройка ("только FBO" / "только FBS").
        // Применяется ПЕРВЫМ, персональная user->orders_priority — ВТОРЫМ (пересечение AND).
        $ordersFilter = Setting::getValue('orders_filter', $workshopId);

        $items = match ($ordersFilter) {
            'fbo' => $items->where('marketplace_orders.fulfillment_type', 'FBO'),
            'fbs' => $items->where('marketplace_orders.fulfillment_type', 'FBS'),
            default => $items, // 'all' или null — без фильтра
        };

        // Персональный приоритет заказов
        $items = match (auth()->user()->orders_priority) {
            'fbo' => $items->where('marketplace_orders.fulfillment_type', 'FBO'),
            'fbo_200' => $items->where('marketplace_orders.fulfillment_type', 'FBO')
                ->where('marketplace_items.width', 200),
            default => $items
        };

        // Приоритет заказов (цеховая настройка)
        $ordersPriority = Setting::getValue('orders_priority', $workshopId);

        $items = match ($ordersPriority) {
            'ozon' => $items->orderBy('marketplace_orders.marketplace_id', 'asc'),
            'wb' => $items->orderBy('marketplace_orders.marketplace_id', 'desc'),
            default => $items
        };

        return $items
            ->orderBy('marketplace_orders.created_at', 'asc')
            ->orderBy('marketplace_order_items.id', 'asc')
            ->groupBy('marketplace_order_items.id')
            ->select('marketplace_order_items.*')
            ->get();
    }

    /**
     * Сбрасывает цеховую настройку orders_cluster_priority, если в очереди (до стикеровки)
     * не осталось заказов этого кластера — ни привязанных к цеху, ни новых нераспределённых.
     *
     * При $workshopId = null (складской сценарий: кладовщик сдаёт нераспределённый заказ,
     * числящийся в очереди каждого цеха) проверяются все цехи с выставленной цеховой
     * настройкой — истощённые сбрасываются. Глобальная запись (workshop_id IS NULL)
     * не затрагивается.
     *
     * @param  int|null  $workshopId  ID цеха или null для проверки всех цехов
     */
    public static function resetClusterPriorityIfExhausted(?int $workshopId): void
    {
        if ($workshopId !== null) {
            self::resetClusterPriorityForWorkshop($workshopId);

            return;
        }

        // Складской сценарий — проверить каждый цех с выставленным приоритетом кластера
        $workshopIds = Setting::query()
            ->where('name', 'orders_cluster_priority')
            ->whereNotNull('workshop_id')
            ->where('value', 'like', '%|%')
            ->pluck('workshop_id')
            ->unique();

        foreach ($workshopIds as $id) {
            self::resetClusterPriorityForWorkshop((int) $id);
        }
    }

    /**
     * Сбрасывает цеховую настройку orders_cluster_priority для конкретного цеха,
     * если заказы этого кластера в очереди (до стикеровки) исчерпаны.
     *
     * @param  int  $workshopId  ID цеха
     */
    private static function resetClusterPriorityForWorkshop(int $workshopId): void
    {
        // Читаем только цеховую настройку (глобальную не трогаем)
        $clusterPriority = Setting::query()
            ->where('name', 'orders_cluster_priority')
            ->where('workshop_id', $workshopId)
            ->value('value');

        if (! $clusterPriority || ! str_contains($clusterPriority, '|')) {
            return;  // выключено или нет данных
        }

        [$mpId, $cluster] = explode('|', $clusterPriority, 2);

        // Счётчик: order_items этого кластера в статусах очереди [0,4,7,8]
        // в этом цехе ИЛИ новые нераспределённые (workshop_id IS NULL)
        $remaining = MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->where('marketplace_orders.marketplace_id', (int) $mpId)
            ->where('marketplace_orders.cluster', $cluster)
            ->where(fn ($q) => $q->where('marketplace_order_items.workshop_id', $workshopId)
                ->orWhereNull('marketplace_order_items.workshop_id'))
            ->whereIn('marketplace_order_items.status', [0, 4, 7, 8])  // всё до стикеровки
            ->count();

        if ($remaining === 0) {
            Setting::query()
                ->where('name', 'orders_cluster_priority')
                ->where('workshop_id', $workshopId)
                ->update(['value' => '']);

            Log::channel('system')->info(
                "Приоритет FBO-кластера сброшен (цех {$workshopId}): {$mpId}|{$cluster} — все заказы сданы в стикеровку."
            );
        }
    }

    /**
     * Проверка возможности использования материала этим сотрудником.
     */
    private static function canUseMaterial(MarketplaceOrderItem $marketplaceOrderItem): bool
    {
        $clothConsumptions = $marketplaceOrderItem->item->consumption
            ->filter(fn ($consumption) => $consumption->material->type_id === 1);

        $allowedMaterialIds = auth()->user()->materials->pluck('id');

        if ($allowedMaterialIds->isEmpty()) {
            return true;
        }

        return $clothConsumptions->every(
            fn ($consumption) => $allowedMaterialIds->contains($consumption->material_id)
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

        Log::channel('items')
            ->info('Зарезервировали заказ '.$marketplaceOrder->id.' с товаром '.$marketplaceOrderItem->id);
    }

    public static function saveOrderToHistory(MarketplaceOrderItem $marketplaceOrderItem): void
    {
        MarketplaceOrderHistory::firstOrCreate([
            'marketplace_order_id' => $marketplaceOrderItem->marketplace_order_id,
            'marketplace_order_item_id' => $marketplaceOrderItem->id,
            'status' => 'returned',
        ]);

        Log::channel('items')
            ->info('Заказ '.$marketplaceOrderItem->marketplaceOrder->order_id.' сохранен в историю с товаром '
                .$marketplaceOrderItem->id.' (значит этот заказ отмененный, раз товар на хранении)');

        $marketplaceOrderItem->marketplaceOrder->status = '9'; // возврат
        $marketplaceOrderItem->marketplaceOrder->save();
    }

    public static function restoreOrderFromHistory(MarketplaceOrderItem $selectedItem): void
    {
        try {
            // FIXME: рассмотреть разбиение длинных транзакций на более мелкие атомарные операции
            DB::beginTransaction();

            $marketplaceOrderHistory = $selectedItem->history()
                ->orderByDesc('created_at')
                ->first();

            if (! $marketplaceOrderHistory) {
                Log::channel('items')
                    ->error('В Истории нет заказа '.$selectedItem->marketplace_order_id.' по товару '.$selectedItem->id);
                DB::rollBack();

                return;
            }

            Log::channel('items')
                ->info('Для товара id '.$selectedItem->id.'Восстановлен заказ #'.$marketplaceOrderHistory->marketplace_order_id.
                    ' вместо текущего заказа #'.$selectedItem->marketplace_order_id);

            $selectedItem->marketplace_order_id = $marketplaceOrderHistory->marketplace_order_id;
            $selectedItem->status = 11; // на хранении
            $selectedItem->save();

            $marketplaceOrderHistory->delete();

            DB::commit(); // фиксируем изменения
        } catch (Exception $e) {
            DB::rollBack();

            Log::channel('items')->error('Ошибка при восстановлении заказа из истории: '.$e->getMessage(), [
                'marketplace_order_item_id' => $selectedItem->id,
                'marketplace_order_id' => $selectedItem->marketplace_order_id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    //    private static function isReserved($marketplaceOrderItem): bool
    //    {
    //        if ($marketplaceOrderItem->status == 99) {
    //            Log::channel('items')
    //                ->warning('Конкурентный доступ! Заказ '.$marketplaceOrderItem->id.' находится в резерве');
    //
    //            return true;
    //        }
    //
    //        return false;
    //    }

    //    private static function reserve($marketplaceOrderItem): void
    //    {
    //        $marketplaceOrderItem->status = 99;
    //        $marketplaceOrderItem->save();
    //
    //        Log::channel('items')
    //            ->warning('Зарезервирован товар '.$marketplaceOrderItem->id);
    //    }

    private static function restoreReserve($marketplaceOrderItem): void
    {
        $marketplaceOrderItem->status = ($marketplaceOrderItem->cutter_id) ? 8 : 0;
        $marketplaceOrderItem->save();

        Log::channel('items')
            ->warning('Восстановлен резервированный товар '.$marketplaceOrderItem->id);
    }

    /**
     * Проверка дневного лимита сотрудника.
     */
    private static function checkDailyLimit(User $user): array
    {
        $meters = self::getMetersTodayByUser($user) / 100;

        $currentWorkshopId = $user->currentWorkshop()?->id;

        $dailyLimit = match ($user->role->name) {
            'seamstress' => Setting::getValue('seamstress_daily_limit', $currentWorkshopId),
            'cutter' => Setting::getValue('cutter_daily_limit', $currentWorkshopId),
            default => 0,
        };

        if ($meters >= $dailyLimit) {
            Log::channel('worker_limits')
                ->error('Сотрудник '.$user->name.' достиг дневного лимита! Метраж (готовый и в работе): '.
                    $meters.', при лимите в '.$dailyLimit);

            return [
                'success' => false,
                'message' => 'Вы не можете взять больше заказов сегодня! Ваш метраж (готовый и в работе): '.
                    $meters.', при лимите в '.$dailyLimit,
            ];
        }

        return [
            'success' => true,
            'message' => 'OK',
        ];
    }

    public static function getMetersTodayByUser(User $user): float
    {
        $column = $user->isCutter() ? 'cutter_id' : 'seamstress_id';

        return MarketplaceOrderItem::query()
            ->where($column, $user->id)
            ->when($user->isCutter(), function ($q) {
                $q->where(function ($q) {
                    $q->where('status', 7)
                        ->orWhereDate('cutting_completed_at', now()->toDateString());
                });
            })
            ->when($user->isSeamstress(), function ($q) {
                $q->where(function ($q) {
                    $q->where('status', 4)
                        ->orWhereDate('completed_at', now()->toDateString());
                });
            })
            ->whereHas('item')
            ->with('item')
            ->get()
            ->sum(fn ($orderItem) => $orderItem->item->width ?? 0);
    }

    private static function checkTimeout(User $user): array
    {
        $success = [
            'success' => true,
            'message' => 'OK',
        ];

        if ($user->isCutter()) {
            return $success;
        }

        $inWork = MarketplaceOrderItem::query()
            ->where('status', 4)
            ->where('seamstress_id', $user->id)
            ->with('item')
            ->get();

        $currentWorkshopId = $user->currentWorkshop()?->id;

        $maxCount = Setting::getValue('max_quantity_orders_without_timeout', $currentWorkshopId);

        if ($maxCount > $inWork->count()) {
            return $success;
        }

        $maxRemainingMinutes = 0;

        $timeout = self::getTimeout($currentWorkshopId);

        foreach ($inWork as $orderItem) {
            $orderItemTimeout = $timeout[$orderItem->item->width] ?? 0;

            if ($orderItemTimeout === 0 || $orderItem->started_at === null) {
                continue;
            }

            $deadline = Carbon::parse($orderItem->started_at)
                ->addMinutes($orderItemTimeout);

            if ($deadline->isFuture()) {
                $remainingMinutes = (int) ceil(now()->diffInSeconds($deadline) / 60);
                $maxRemainingMinutes = max($maxRemainingMinutes, $remainingMinutes);
            }
        }

        if ($maxRemainingMinutes > 0) {
            Log::channel('worker_limits')
                ->error('Сотрудник '.$user->name.
                    ' пытался взять заказ, но у него не окончен таймаут. Ждать еще '
                    .$maxRemainingMinutes.' минут');

            return [
                'success' => false,
                'message' => 'Вы не можете взять новый заказ. Подождите '.$maxRemainingMinutes.' минут',
            ];
        }

        return $success;
    }

    /**
     * Проверяет, истёк ли таймаут выполнения для позиции заказа (по ширине и цеху).
     */
    public function checkTimeoutOrderItem(MarketplaceOrderItem $marketplaceOrderItem): bool
    {
        $timeout = self::getTimeout(auth()->user()->currentWorkshop()?->id);

        $orderItemTimeout = $timeout[$marketplaceOrderItem->item->width] ?? 0;

        $deadline = Carbon::parse($marketplaceOrderItem->started_at)
            ->addMinutes($orderItemTimeout);

        $remainingMinutes = 0;
        if ($deadline->isFuture()) {
            $remainingMinutes = (int) ceil(now()->diffInSeconds($deadline) / 60);
        }

        if ($remainingMinutes > 0) {
            return false;
        }

        return true;
    }

    /**
     * Получить таймауты по ширинам для цеха.
     */
    private static function getTimeout(?int $workshopId = null): array
    {
        $settings = Setting::getValues([
            'timeout_200',
            'timeout_300',
            'timeout_400',
            'timeout_500',
            'timeout_600',
            'timeout_700',
            'timeout_800',
        ], $workshopId);

        return [
            200 => (int) ($settings['timeout_200'] ?? 0),
            300 => (int) ($settings['timeout_300'] ?? 0),
            400 => (int) ($settings['timeout_400'] ?? 0),
            500 => (int) ($settings['timeout_500'] ?? 0),
            600 => (int) ($settings['timeout_600'] ?? 0),
            700 => (int) ($settings['timeout_700'] ?? 0),
            800 => (int) ($settings['timeout_800'] ?? 0),
        ];
    }

    /**
     * Возвращает заказы пользователя, сгруппированные по материалу и отсортированные по ширине/высоте.
     */
    public function getOrdersGroupedByMaterial(User $user): \Illuminate\Support\Collection
    {
        $items = MarketplaceOrderItem::query()
            ->with('marketplaceOrder')
            ->with('item')
            ->when($user->isCutter(), fn ($q) => $q
                ->where('marketplace_order_items.status', 7)
                ->where('marketplace_order_items.cutter_id', $user->id)
            )
            ->when($user->isSeamstress(), fn ($q) => $q
                ->where('marketplace_order_items.status', 4)
                ->where('marketplace_order_items.seamstress_id', $user->id)
            )
            ->get();

        return collect($items)
            ->groupBy(fn ($item) => $item->item->title ?? '-----')
            ->map(function ($group) {
                return $group->sortBy(function ($item) {
                    return [$item->item->width, $item->item->height];
                });
            });
    }
}
