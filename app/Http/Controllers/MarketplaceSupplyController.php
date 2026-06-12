<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Models\MarketplaceWarehouse;
use App\Services\MarketplaceApiService;
use App\Services\MarketplaceOrderService;
use App\Services\MarketplaceSupplyService;
use App\Services\TgService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MarketplaceSupplyController extends Controller
{
    public function index(Request $request)
    {
        $supplies = MarketplaceSupply::query()
            ->orderByRaw('CASE WHEN gazelka_shipment_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('gazelka_shipment_date', 'asc')
            ->orderBy('id', 'asc');

        if ($request->status == 3) {
            $supplies = $supplies->where('status', 3);
        } elseif ($request->status == 0) {
            $supplies = $supplies->whereIn('status', [0, 4, 13]);
        } else {
            $supplies = match (auth()->user()->role->name) {
                'driver' => $supplies->where('status', 4),
                default => $supplies->where('status', '!=', 3),
            };
        }

        if ($request->filled('type')) {
            $supplies = $supplies->where('type', $request->type);
        }

        if (isset($request->marketplace_id)) {
            $supplies = $supplies->where('marketplace_id', $request->marketplace_id);
        }

        if (isset($request->search)) {
            $supplies = $supplies->where(function ($query) use ($request) {
                $query->where('supply_id', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('date_from')) {
            $supplies = $supplies->where('gazelka_shipment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $supplies = $supplies->where('gazelka_shipment_date', '<=', $request->date_to);
        }

        $queryParams = $request->except(['page']);

        return view('marketplace_supply.index', [
            'title' => 'Поставки маркетплейса',
            'marketplace_supplies' => $supplies->paginate(10)->appends($queryParams),
        ]);
    }

    public function show(MarketplaceSupply $marketplaceSupply)
    {
        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceSupply->marketplace_id);

        if ($marketplaceSupply->type === 'FBO' && $marketplaceSupply->marketplace_id == 1) {
            $ozonSupplyOrders = ($marketplaceSupply->status === 0 && empty($marketplaceSupply->supply_id))
                ? self::getOzonSupplyOrdersForDropdown()
                : [];

            $hasOrders = MarketplaceOrder::query()
                ->where('supply_id', $marketplaceSupply->id)
                ->exists();

            $supplyOrders = $hasOrders
                ? MarketplaceOrder::query()
                    ->with('items.item', 'box')
                    ->where('supply_id', $marketplaceSupply->id)
                    ->get()
                : collect();

            $hasNewOrders = $supplyOrders->contains(fn ($order) => $order->status == 0);
            $hasNotReadyOrders = $supplyOrders->contains(fn ($order) => $order->box_id === null && $order->status == 4);

            return view('marketplace_supply.show-ozon-fbo', [
                'title' => 'Поставка для маркетплейса '.$marketplaceName,
                'supply' => $marketplaceSupply,
                'ozonSupplyOrders' => $ozonSupplyOrders,
                'hasOrders' => $hasOrders,
                'supplyOrders' => $supplyOrders,
                'hasNewOrders' => $hasNewOrders,
                'hasNotReadyOrders' => $hasNotReadyOrders,
            ]);
        }

        if ($marketplaceSupply->type === 'FBO' && $marketplaceSupply->marketplace_id == 2) {
            $wbSupplies = ($marketplaceSupply->status === 0 && empty($marketplaceSupply->supply_id))
                ? MarketplaceApiService::getFboSuppliesWb()
                : [];

            $hasOrders = MarketplaceOrder::query()
                ->where('supply_id', $marketplaceSupply->id)
                ->exists();

            $supplyOrders = $hasOrders
                ? MarketplaceOrder::query()
                    ->with('items.item', 'box')
                    ->where('supply_id', $marketplaceSupply->id)
                    ->get()
                : collect();

            $hasNewOrders = $supplyOrders->contains(fn ($order) => $order->status == 0);
            $hasNotReadyOrders = $supplyOrders->contains(fn ($order) => $order->box_id === null && $order->status == 4);

            $canExportExcel = $marketplaceSupply->status === 4;

            return view('marketplace_supply.show-wb-fbo', [
                'title' => 'Поставка для маркетплейса '.$marketplaceName,
                'supply' => $marketplaceSupply,
                'wbSupplies' => $wbSupplies,
                'hasOrders' => $hasOrders,
                'supplyOrders' => $supplyOrders,
                'hasNewOrders' => $hasNewOrders,
                'hasNotReadyOrders' => $hasNotReadyOrders,
                'canExportExcel' => $canExportExcel,
            ]);
        }

        return view('marketplace_supply.show', [
            'title' => 'Поставка для маркетплейса '.$marketplaceName,
            'supply' => $marketplaceSupply,
            'hasShippedOrders' => MarketplaceOrderService::hasShippedOrdersBySupply($marketplaceSupply),
            'supply_orders' => $marketplaceSupply->marketplace_orders()->get(),
        ]);
    }

    /**
     * Привязка выбранной FBO-поставки из WB к поставке в системе.
     */
    public function linkWbFbo(Request $request, MarketplaceSupply $marketplaceSupply)
    {
        $validated = $request->validate([
            'wb_supply_id' => 'required|integer',
        ]);

        $exists = MarketplaceSupply::query()
            ->where('supply_id', (string) $validated['wb_supply_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Поставка с номером '.$validated['wb_supply_id'].' уже привязана.');
        }

        $detail = MarketplaceApiService::getFboSupplyDetailWb((int) $validated['wb_supply_id']);

        if (empty($detail)) {
            return back()->with('error', 'Не удалось получить данные поставки из WB.');
        }

        $marketplaceSupply->update([
            'supply_id' => (string) $validated['wb_supply_id'],
            'cluster' => $detail['warehouseName'] ?? null,
            'supply_date' => isset($detail['supplyDate']) ? Carbon::parse($detail['supplyDate']) : null,
        ]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' привязал FBO-поставку WB #'.$validated['wb_supply_id'].' к поставке #'.$marketplaceSupply->id.'.');

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Поставка привязана.');
    }

    /**
     * Загрузка товарного состава FBO-поставки из WB API.
     */
    public function loadFboGoods(MarketplaceSupply $marketplaceSupply)
    {
        $goods = MarketplaceApiService::getFboSupplyGoodsWb((int) $marketplaceSupply->supply_id);

        if (empty($goods)) {
            return back()->with('error', 'Не удалось получить товарный состав из WB.');
        }

        $vendorCodes = collect($goods)->pluck('vendorCode')->unique()->filter()->values()->toArray();

        $items = MarketplaceItem::query()
            ->whereIn('article', $vendorCodes)
            ->get()
            ->keyBy('article');

        $supplyGoods = collect($goods)->map(function (array $good) use ($items) {
            $item = $items->get($good['vendorCode']);

            return [
                'vendorCode' => $good['vendorCode'],
                'name' => $item
                    ? $item->title.' '.$item->width.'x'.$item->height
                    : '-',
                'quantity' => $good['quantity'],
                'found' => $item !== null,
            ];
        });

        $allItemsFound = $supplyGoods->every(fn ($g) => $g['found']);

        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceSupply->marketplace_id);

        return view('marketplace_supply.show-wb-fbo', [
            'title' => 'Поставка для маркетплейса '.$marketplaceName,
            'supply' => $marketplaceSupply,
            'wbSupplies' => [],
            'supplyGoods' => $supplyGoods,
            'allItemsFound' => $allItemsFound,
            'hasOrders' => false,
            'supplyOrders' => collect(),
            'canExportExcel' => false,
        ]);
    }

    /**
     * Форма редактирования полей Газельки для FBO-поставки.
     */
    public function editFbo(MarketplaceSupply $marketplaceSupply)
    {
        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceSupply->marketplace_id);

        return view('marketplace_supply.edit-fbo', [
            'title' => 'Редактирование поставки '.$marketplaceName,
            'supply' => $marketplaceSupply,
        ]);
    }

    /**
     * Сохранение полей Газельки для FBO-поставки.
     */
    public function updateFbo(Request $request, MarketplaceSupply $marketplaceSupply)
    {
        $validated = $request->validate([
            'gazelka_shipment_id' => 'nullable|string',
            'gazelka_shipment_date' => 'nullable|date',
            'delivery_type' => 'nullable|string|in:'.implode(',', MarketplaceSupply::DELIVERY_TYPES),
            'gazelka_pickup' => 'nullable|boolean',
            'boxes_count' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['gazelka_shipment_date']) && $marketplaceSupply->supply_date) {
            $gazelkaDate = Carbon::parse($validated['gazelka_shipment_date']);
            if ($gazelkaDate->gte($marketplaceSupply->supply_date)) {
                return back()
                    ->with('error', 'Дата отгрузки в Газельку должна быть хотя бы на день раньше даты отгрузки в маркетплейс ('.$marketplaceSupply->supply_date->format('d.m.Y').').')
                    ->withInput();
            }
        }

        if (isset($validated['boxes_count']) && ! $marketplaceSupply->canEditBoxesCount()) {
            return back()
                ->with('error', 'Редактирование кол-ва коробов недоступно: дата отгрузки уже наступила.')
                ->withInput();
        }

        $marketplaceSupply->update([
            'gazelka_shipment_id' => $validated['gazelka_shipment_id'] ?? null,
            'gazelka_shipment_date' => isset($validated['gazelka_shipment_date']) ? Carbon::parse($validated['gazelka_shipment_date']) : null,
            'delivery_type' => $validated['delivery_type'] ?? null,
            'gazelka_pickup' => $validated['gazelka_pickup'] ?? null,
            'boxes_count' => $validated['boxes_count'] ?? null,
        ]);

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Данные обновлены.');
    }

    /**
     * Формирование заказов из товарного состава FBO-поставки.
     */
    public function confirmFboGoods(MarketplaceSupply $marketplaceSupply)
    {
        $hasOrders = MarketplaceOrder::query()
            ->where('supply_id', $marketplaceSupply->id)
            ->exists();

        if ($hasOrders) {
            return back()->with('error', 'Заказы для этой поставки уже созданы.');
        }

        $goods = MarketplaceApiService::getFboSupplyGoodsWb((int) $marketplaceSupply->supply_id);

        if (empty($goods)) {
            return back()->with('error', 'Не удалось получить товарный состав из WB.');
        }

        $vendorCodes = collect($goods)->pluck('vendorCode')->unique()->filter()->values()->toArray();

        $items = MarketplaceItem::query()
            ->whereIn('article', $vendorCodes)
            ->get()
            ->keyBy('article');

        $notFound = collect($vendorCodes)->diff($items->keys())->values();

        if ($notFound->isNotEmpty()) {
            return back()->with('error', 'Не найдены товары с артикулами: '.$notFound->implode(', '));
        }

        $orderNumber = 1;

        foreach ($goods as $good) {
            $item = $items->get($good['vendorCode']);

            for ($i = 0; $i < $good['quantity']; $i++) {
                $order = MarketplaceOrder::query()->create([
                    'order_id' => $marketplaceSupply->supply_id.'-'.$orderNumber,
                    'marketplace_id' => 2,
                    'supply_id' => $marketplaceSupply->id,
                    'fulfillment_type' => 'FBO',
                    'cluster' => $marketplaceSupply->cluster,
                    'status' => 0,
                ]);

                MarketplaceOrderItem::query()->create([
                    'marketplace_order_id' => $order->id,
                    'marketplace_item_id' => $item->id,
                    'quantity' => 1,
                    'price' => 0,
                    'status' => 0,
                ]);

                $orderNumber++;
            }
        }

        $marketplaceSupply->update(['status' => 13]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' сформировал FBO-поставку #'.$marketplaceSupply->id.' ('.($orderNumber - 1).' заказов).');

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Поставка сформирована ('.($orderNumber - 1).' заказов).');
    }

    /**
     * Получает список заявок на поставку OZON с деталями для отображения в dropdown.
     *
     * @return array<array{order_id: int, order_number: string, from_time: string|null, to_time: string|null, cluster: string|null}>
     */
    private static function getOzonSupplyOrdersForDropdown(): array
    {
        $orderIds = MarketplaceApiService::getSupplyOrderListOzon();

        if (empty($orderIds)) {
            return [];
        }

        $result = [];

        foreach ($orderIds as $orderId) {
            $details = MarketplaceApiService::getSupplyOrderDetailsOzon((int) $orderId);

            if (empty($details)) {
                continue;
            }

            $supply = $details['supplies'][0] ?? null;
            $timeslot = $details['timeslot']['value']['timeslot'] ?? null;
            $macrolocalClusterId = $supply['macrolocal_cluster_id'] ?? null;

            $clusterName = null;
            if ($macrolocalClusterId) {
                $warehouse = MarketplaceWarehouse::query()
                    ->where('macrolocal_cluster_id', $macrolocalClusterId)
                    ->first();

                $clusterName = $warehouse?->cluster;
            }

            $result[] = [
                'order_id' => $details['order_id'],
                'order_number' => $details['order_number'] ?? null,
                'from_time' => $timeslot['from'] ?? null,
                'to_time' => $timeslot['to'] ?? null,
                'cluster' => $clusterName ?? $macrolocalClusterId,
            ];
        }

        return $result;
    }

    /**
     * Привязка выбранной FBO-заявки на поставку из OZON к поставке в системе.
     */
    public function linkOzonFbo(Request $request, MarketplaceSupply $marketplaceSupply)
    {
        $validated = $request->validate([
            'ozon_order_id' => 'required|integer',
        ]);

        $exists = MarketplaceSupply::query()
            ->where('supply_id', (string) $validated['ozon_order_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Заявка с номером '.$validated['ozon_order_id'].' уже привязана.');
        }

        $details = MarketplaceApiService::getSupplyOrderDetailsOzon((int) $validated['ozon_order_id']);

        if (empty($details)) {
            return back()->with('error', 'Не удалось получить данные заявки из OZON.');
        }

        $supply = $details['supplies'][0] ?? null;
        $timeslot = $details['timeslot']['value']['timeslot'] ?? null;
        $macrolocalClusterId = $supply['macrolocal_cluster_id'] ?? null;
        $bundleId = $supply['content']['bundle_id'] ?? null;
        $isCrossdock = $supply['is_crossdock'] ?? false;

        $clusterName = null;
        if ($macrolocalClusterId) {
            $warehouse = MarketplaceWarehouse::query()
                ->where('macrolocal_cluster_id', $macrolocalClusterId)
                ->first();

            $clusterName = $warehouse?->cluster;
        }

        $marketplaceSupply->update([
            'supply_id' => (string) $details['order_id'],
            'cluster' => $clusterName ?? $macrolocalClusterId,
            'supply_date' => isset($timeslot['from']) ? Carbon::parse($timeslot['from']) : null,
            'supply_type' => $isCrossdock ? 'Кросс-докинг' : 'Прямая поставка',
            'draft_params' => [
                'order_number' => $details['order_number'] ?? null,
                'bundle_id' => $bundleId,
                'macrolocal_cluster_id' => $macrolocalClusterId,
                'timeslot_from' => $timeslot['from'] ?? null,
                'timeslot_to' => $timeslot['to'] ?? null,
            ],
        ]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' привязал FBO-заявку OZON #'.$validated['ozon_order_id'].' к поставке #'.$marketplaceSupply->id.'.');

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Заявка на поставку привязана.');
    }

    /**
     * Загрузка товарного состава FBO-заявки на поставку из OZON API.
     */
    public function loadOzonFboGoods(MarketplaceSupply $marketplaceSupply)
    {
        $bundleId = $marketplaceSupply->draft_params['bundle_id'] ?? null;

        if (empty($bundleId)) {
            return back()->with('error', 'Не найден bundle_id для загрузки товаров.');
        }

        $goods = MarketplaceApiService::getSupplyOrderBundleOzon([$bundleId]);

        if (empty($goods)) {
            return back()->with('error', 'Не удалось получить товарный состав из OZON.');
        }

        $offerIds = collect($goods)->pluck('offer_id')->unique()->filter()->values()->toArray();

        $items = MarketplaceItem::query()
            ->whereIn('article', $offerIds)
            ->get()
            ->keyBy('article');

        $supplyGoods = collect($goods)->map(function (array $good) use ($items) {
            $item = $items->get($good['offer_id']);

            return [
                'offer_id' => $good['offer_id'],
                'name' => $item
                    ? $item->title.' '.$item->width.'x'.$item->height
                    : $good['name'] ?? '-',
                'quantity' => $good['quantity'],
                'found' => $item !== null,
            ];
        });

        $allItemsFound = $supplyGoods->every(fn ($g) => $g['found']);

        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceSupply->marketplace_id);

        return view('marketplace_supply.show-ozon-fbo', [
            'title' => 'Поставка для маркетплейса '.$marketplaceName,
            'supply' => $marketplaceSupply,
            'ozonSupplyOrders' => [],
            'supplyGoods' => $supplyGoods,
            'allItemsFound' => $allItemsFound,
            'hasOrders' => false,
            'supplyOrders' => collect(),
        ]);
    }

    /**
     * Формирование заказов из товарного состава FBO-заявки на поставку OZON.
     */
    public function confirmOzonFboGoods(MarketplaceSupply $marketplaceSupply)
    {
        $hasOrders = MarketplaceOrder::query()
            ->where('supply_id', $marketplaceSupply->id)
            ->exists();

        if ($hasOrders) {
            return back()->with('error', 'Заказы для этой поставки уже созданы.');
        }

        $bundleId = $marketplaceSupply->draft_params['bundle_id'] ?? null;

        if (empty($bundleId)) {
            return back()->with('error', 'Не найден bundle_id для загрузки товаров.');
        }

        $goods = MarketplaceApiService::getSupplyOrderBundleOzon([$bundleId]);

        if (empty($goods)) {
            return back()->with('error', 'Не удалось получить товарный состав из OZON.');
        }

        $offerIds = collect($goods)->pluck('offer_id')->unique()->filter()->values()->toArray();

        $items = MarketplaceItem::query()
            ->whereIn('article', $offerIds)
            ->get()
            ->keyBy('article');

        $notFound = collect($offerIds)->diff($items->keys())->values();

        if ($notFound->isNotEmpty()) {
            return back()->with('error', 'Не найдены товары с артикулами: '.$notFound->implode(', '));
        }

        $orderNumber = 1;

        foreach ($goods as $good) {
            $item = $items->get($good['offer_id']);

            for ($i = 0; $i < $good['quantity']; $i++) {
                $order = MarketplaceOrder::query()->create([
                    'order_id' => $marketplaceSupply->supply_id.'-'.$orderNumber,
                    'marketplace_id' => 1,
                    'supply_id' => $marketplaceSupply->id,
                    'fulfillment_type' => 'FBO',
                    'cluster' => $marketplaceSupply->cluster,
                    'status' => 0,
                ]);

                MarketplaceOrderItem::query()->create([
                    'marketplace_order_id' => $order->id,
                    'marketplace_item_id' => $item->id,
                    'quantity' => 1,
                    'price' => 0,
                    'status' => 0,
                ]);

                $orderNumber++;
            }
        }

        $marketplaceSupply->update(['status' => 13]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' сформировал FBO-поставку OZON #'.$marketplaceSupply->id.' ('.($orderNumber - 1).' заказов).');

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Поставка сформирована ('.($orderNumber - 1).' заказов).');
    }

    /**
     * Создание новой поставки для маркетплейса.
     */
    public function create(string $marketplace_id)
    {
        $type = request()->input('type', 'FBS');
        $user = auth()->user();

        if (! $user->isAdmin()) {
            if ($user->isManager() && $type !== 'FBO') {
                abort(403, 'Менеджер может создавать только FBO поставки.');
            }
            if ($user->isStorekeeper() && $type !== 'FBS') {
                abort(403, 'Кладовщик может создавать только FBS поставки.');
            }
        }

        if ($marketplace_id == 1 && $type === 'FBS') {
            $openSupplyOzon = MarketplaceSupply::query()
                ->where('marketplace_id', 1)
                ->where('type', 'FBS')
                ->where('status', 0)
                ->count();

            if ($openSupplyOzon > 0) {
                return redirect()
                    ->route('marketplace_supplies.index')
                    ->with('error', 'Уже есть открытая поставка OZON FBS.');
            }
        }

        $marketplaceSupply = MarketplaceSupply::query()->create([
            'marketplace_id' => $marketplace_id,
            'type' => $type,
        ]);

        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplaceSupply->marketplace_id);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' создал поставку для маркетплейса '.$marketplaceName.' ('.strtoupper($type).') (#'.$marketplaceSupply->id.').');

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка создана.');
    }

    public function destroy(MarketplaceSupply $marketplace_supply)
    {
        if ($marketplace_supply->marketplace_orders->count() > 0) {
            return back()
                ->with('error', 'Нельзя удалить поставку, которая содержит заказы.');
        }

        if ($marketplace_supply->supply_id && ! $marketplace_supply->draft_id && $marketplace_supply->marketplace_id === 1 && $marketplace_supply->type === 'FBO') {
            $cancelResult = MarketplaceApiService::cancelSupplyOzon((int) $marketplace_supply->supply_id);

            if ($cancelResult['error']) {
                return redirect()
                    ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                    ->with('error', 'Ошибка отмены поставки OZON: '.$cancelResult['error']);
            }

            sleep(2);

            $maxAttempts = 5;

            for ($i = 0; $i < $maxAttempts; $i++) {
                $status = MarketplaceApiService::getCancelSupplyStatusOzon($cancelResult['operation_id']);

                if ($status['status'] === 'SUCCESS') {
                    break;
                }

                if ($status['status'] === 'FAILED') {
                    $errors = implode(', ', $status['error_reasons']);

                    return redirect()
                        ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                        ->with('error', 'Отмена поставки OZON не удалась: '.$errors);
                }

                sleep(2);
            }

            if (($status['status'] ?? '') !== 'SUCCESS') {
                return redirect()
                    ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                    ->with('error', 'Не удалось получить статус отмены поставки за отведённое время.');
            }
        }

        MarketplaceSupply::query()->findOrFail($marketplace_supply->id)->delete();

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка удалена.');
    }

    public function complete(MarketplaceSupply $marketplace_supply)
    {
        if ($marketplace_supply->marketplace_orders->count() == 0) {
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Поставка не содержит заказов.');
        }

        //  обновить статусы заказов в поставке
        $isUpdated = match ($marketplace_supply->marketplace_id) {
            1 => MarketplaceApiService::updateStatusOrderBySupplyOzon($marketplace_supply),
            2 => MarketplaceApiService::updateStatusOrderBySupplyWB($marketplace_supply),
            default => false
        };

        if (! $isUpdated) {
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось обновить статусы заказов перед формированием поставки! Попробуйте повторить позже.');
        }

        //  Проверить статусы всех заказов в поставке и если есть уже отгруженные - не давать сборку
        $checkStatusOrders = MarketplaceOrderService::hasShippedOrdersBySupply($marketplace_supply);
        if ($checkStatusOrders) {
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Невозможно сформировать поставку! В поставке есть заказы с неподходящим статусом.');
        }

        $result = match ($marketplace_supply->marketplace_id) {
            1 => MarketplaceApiService::ozonSupply($marketplace_supply),
            2 => MarketplaceApiService::wbSupply($marketplace_supply),
            default => false
        };

        if (! $result) {
            return redirect()
                ->route('marketplace_supplies.index')
                ->with('error', 'Ошибка! Не удалось выполнить сборку поставки.');
        }

        if ($marketplace_supply->video == null) {
            $text = 'Внимание! Кладовщик '.auth()->user()->name.
                ' не загрузил видео к поставке № '.$marketplace_supply->id.
                '. Запросите видео у кладовщика и загрузите его самостоятельно.';

            Log::channel('tg')
                ->error('Отправили сообщение в ТГ админу: '.$text);

            TgService::sendMessage(config('telegram.admin_id'), $text);
        }

        $marketplace_supply->update([
            'status' => 4,
            'completed_at' => now(),
        ]);

        $marketplace_supply->marketplace_orders()->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplace_supply->marketplace_id);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' передал в отгрузку поставку #'.$marketplace_supply->id.' для маркетплейса '.$marketplaceName.'.');

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка сформирована.');
    }

    public function done(MarketplaceSupply $marketplace_supply)
    {
        $marketplace_supply->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplace_supply->marketplace_id);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' сдал поставку #'.$marketplace_supply->id.' в маркетплейс '.$marketplaceName.'.');

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка сдана в маркетплейс.');
    }

    public function getDocs(MarketplaceSupply $marketplace_supply)
    {
        $isFormed = MarketplaceApiService::checkStatusSupplyOzon($marketplace_supply);
        if (! $isFormed) {
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Документы еще не сформированы.');
        }

        return MarketplaceApiService::getDocsSupplyOzon($marketplace_supply);
    }

    public function getBarcode(MarketplaceSupply $marketplace_supply)
    {
        return match ($marketplace_supply->marketplace_id) {
            1 => MarketplaceApiService::getBarcodeSupplyOzon($marketplace_supply),
            2 => MarketplaceApiService::getBarcodeSupplyWB($marketplace_supply),
            default => redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'В поставке указан некорректный маркетплейс!'),
        };
    }

    public function updateStatusOrders(MarketplaceSupply $marketplace_supply)
    {
        switch ($marketplace_supply->marketplace_id) {
            case 1:
                $isUpdated = MarketplaceApiService::updateStatusOrderBySupplyOzon($marketplace_supply);
                break;
            case 2:
                $isUpdated = MarketplaceApiService::updateStatusOrderBySupplyWB($marketplace_supply);
                break;
            default:
                return redirect()
                    ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                    ->with('error', 'В поставке указан некорректный маркетплейс!');
        }

        if (! $isUpdated) {
            return redirect()
                ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
                ->with('error', 'Не удалось обновить статусы заказов!');
        }

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' обновил статусы заказов для поставки #'.$marketplace_supply->id.'.');

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
            ->with('success', 'Статусы заказов обновлены.');
    }

    public function delete_video(MarketplaceSupply $marketplace_supply)
    {
        $video = $marketplace_supply->video;

        if (Storage::disk('public')->exists('videos/'.$video)) {
            Storage::disk('public')->delete('videos/'.$video);
        }

        $marketplace_supply->update([
            'video' => null,
        ]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' удалил видео для поставки #'.$marketplace_supply->id.'.');

        return back()->with('success', 'Видео удалено!');
    }

    public function chunkedUpload(Request $request)
    {
        return MarketplaceSupplyService::chunkedUpload($request);
    }

    /**
     * Загрузка PDF-стикера пропуска для поставки.
     */
    public function uploadSticker(Request $request, MarketplaceSupply $marketplaceSupply)
    {
        $validated = $request->validate([
            'sticker' => 'required|file|mimes:pdf|max:10240',
        ]);

        $fileName = 'supply_'.$marketplaceSupply->id.'.pdf';

        Storage::disk('public')->putFileAs('stickers', $validated['sticker'], $fileName);

        $marketplaceSupply->update(['sticker' => $fileName]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' загрузил стикер пропуска для поставки #'.$marketplaceSupply->id.'.');

        return back()->with('success', 'Стикер пропуска загружен.');
    }

    /**
     * Скачивание PDF-стикера пропуска поставки.
     */
    public function downloadSticker(MarketplaceSupply $marketplaceSupply)
    {
        if (! $marketplaceSupply->sticker || ! Storage::disk('public')->exists('stickers/'.$marketplaceSupply->sticker)) {
            return back()->with('error', 'Стикер пропуска не найден.');
        }

        return Storage::disk('public')->download('stickers/'.$marketplaceSupply->sticker);
    }

    /**
     * Удаление PDF-стикера пропуска поставки.
     */
    public function deleteSticker(MarketplaceSupply $marketplaceSupply)
    {
        if (Storage::disk('public')->exists('stickers/'.$marketplaceSupply->sticker)) {
            Storage::disk('public')->delete('stickers/'.$marketplaceSupply->sticker);
        }

        $marketplaceSupply->update(['sticker' => null]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' удалил стикер пропуска для поставки #'.$marketplaceSupply->id.'.');

        return back()->with('success', 'Стикер пропуска удалён.');
    }

    /**
     * Загрузка PDF-накладной от Газельки для поставки.
     */
    public function uploadGazelkaInvoice(Request $request, MarketplaceSupply $marketplaceSupply)
    {
        $validated = $request->validate([
            'gazelka_invoice' => 'required|file|mimes:pdf|max:10240',
        ]);

        $fileName = 'gazelka_invoice_'.$marketplaceSupply->id.'.pdf';

        Storage::disk('public')->putFileAs('gazelka_invoices', $validated['gazelka_invoice'], $fileName);

        $marketplaceSupply->update(['gazelka_invoice' => $fileName]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' загрузил накладную от Газельки для поставки #'.$marketplaceSupply->id.'.');

        return back()->with('success', 'Накладная от Газельки загружена.');
    }

    /**
     * Скачивание PDF-накладной от Газельки.
     */
    public function downloadGazelkaInvoice(MarketplaceSupply $marketplaceSupply)
    {
        if (! $marketplaceSupply->gazelka_invoice || ! Storage::disk('public')->exists('gazelka_invoices/'.$marketplaceSupply->gazelka_invoice)) {
            return back()->with('error', 'Накладная от Газельки не найдена.');
        }

        return Storage::disk('public')->download('gazelka_invoices/'.$marketplaceSupply->gazelka_invoice);
    }

    /**
     * Удаление PDF-накладной от Газельки.
     */
    public function deleteGazelkaInvoice(MarketplaceSupply $marketplaceSupply)
    {
        if (Storage::disk('public')->exists('gazelka_invoices/'.$marketplaceSupply->gazelka_invoice)) {
            Storage::disk('public')->delete('gazelka_invoices/'.$marketplaceSupply->gazelka_invoice);
        }

        $marketplaceSupply->update(['gazelka_invoice' => null]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' удалил накладную от Газельки для поставки #'.$marketplaceSupply->id.'.');

        return back()->with('success', 'Накладная от Газельки удалена.');
    }

    /**
     * Пометить FBO-поставку как отгруженную (статус 3).
     */
    public function markShipped(MarketplaceSupply $marketplaceSupply)
    {
        if ($marketplaceSupply->status === 3) {
            return back()->with('error', 'Поставка уже отгружена.');
        }

        if ($marketplaceSupply->marketplace_id == 2 && ! $marketplaceSupply->sticker) {
            return back()->with('error', 'Сначала загрузите стикер пропуска.');
        }

        $marketplaceSupply->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' пометил поставку #'.$marketplaceSupply->id.' как отгруженную.');

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Поставка отгружена.');
    }

    public function close(MarketplaceSupply $marketplace_supply)
    {
        $marketplace_supply->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        $marketplace_supply->marketplace_orders()->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        $marketplaceName = MarketplaceOrderService::getMarketplaceName($marketplace_supply->marketplace_id);
        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' Вручную закрыл поставку #'.$marketplace_supply->id.' в маркетплейс '.$marketplaceName.'.');

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка закрыта вручную.');
    }
}
