<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceWarehouse;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\ProductSticker;
use App\Models\Roll;
use App\Models\Setting;
use App\Models\ShiftSchedule;
use App\Models\Sku;
use App\Models\User;
use App\Models\Workshop;
use App\Services\MarketplaceApiService;
use App\Services\MarketplaceItemService;
use App\Services\MarketplaceOrderItemService;
use App\Services\ShiftService;
use App\Services\StackService;
use App\Services\StickerService;
use App\Services\TransactionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketplaceOrderItemController extends Controller
{
    /**
     * Список товаров для пошива с фильтрацией по статусу.
     */
    public function index(Request $request)
    {
        // Если статус не указан — редирект на статус по умолчанию для роли
        if (! $request->has('status') || $request->status === null) {
            $defaultStatus = match (auth()->user()->role->name) {
                'otk' => 'cut',
                'cutter' => 'cutting',
                'seamstress', 'storekeeper' => 'in_work',
                default => 'new',
            };

            session()->reflash();

            return redirect()->route('marketplace_order_items.index', ['status' => $defaultStatus]);
        }

        //  запретить швеям смотреть новые заказы и заказы в закрое.
        if (($request->status == 'new' || $request->status == 'cutting') && auth()->user()->isSeamstress()) {
            return redirect()->route('marketplace_order_items.index', ['status' => 'in_work']);
        }

        // запретить закройщикам смотреть новые заказы
        if ($request->status == 'new' && auth()->user()->isCutter()) {
            return redirect()->route('marketplace_order_items.index', ['status' => 'cutting']);
        }

        // запретить ОТК смотреть новые заказы
        if ($request->status == 'new' && auth()->user()->isOtk()) {
            return redirect()->route('marketplace_order_items.index', ['status' => 'cut']);
        }

        $items = MarketplaceOrderItemService::getFiltered($request);
        $paginatedItems = $items->paginate(50);

        $queryParams = $request->except(['page']);

        return view('marketplace_order_items.index', [
            'title' => 'Товары для пошива',
            'items' => $paginatedItems->appends($queryParams),
            //            'materials' => InventoryService::materialsQuantityBy('workhouse'),
            'bonus' => TransactionService::getBonusForTodayOrdersByUsers(),
            'users' => User::query()->whereIn('role_id', [1, 4, 5])
                ->where('name', 'not like', '%Тест%')->get(),
            'titleMaterials' => MarketplaceItemService::getAllTitleMaterials(),
            'widthMaterials' => MarketplaceItemService::getAllWidthMaterials(),
            'heightMaterials' => MarketplaceItemService::getAllHeightMaterials(),
            'workshops' => Workshop::query()->active()->get(),
        ]);
    }

    public function show(MarketplaceOrderItem $marketplaceOrderItem)
    {
        $marketplaceOrderItem->load([
            'marketplaceOrder',
            'item.consumption.material.rolls',
            'seamstress',
            'cutter',
            'otk',
            'repacker',
            'shelf',
            'history',
        ]);

        $bonus = TransactionService::getBonusForTodayOrdersByUsers();

        // whereIn вместо whereHas: заставляет MySQL идти от селективной orders
        // (по marketplace_order_id) и соединять movement_materials по индексу order_id.
        // whereHas (= WHERE EXISTS) давал full scan всех movement_materials (~85k строк).
        $assignedRolls = MovementMaterial::query()
            ->whereIn('order_id', Order::query()
                ->where('type_movement', 3)
                ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id)
                ->select('id'))
            ->whereNotNull('roll_id')
            ->pluck('roll_id', 'material_id');

        //        $assignedRolls = MovementMaterial::query()
        //            ->whereHas('order', function ($query) use ($marketplaceOrderItem) {
        //                $query->where('type_movement', 3)
        //                    ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id);
        //            })
        //            ->whereNotNull('roll_id')
        //            ->pluck('roll_id', 'material_id');

        return view('marketplace_order_items.show', [
            'title' => 'Карточка товара #'.$marketplaceOrderItem->id,
            'item' => $marketplaceOrderItem,
            'bonus' => $bonus,
            'assignedRolls' => $assignedRolls,
        ]);
    }

    public function done(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        $user = User::find(session('user_id'));

        if (! $user) {
            return redirect()
                ->back()
                ->with('error', 'Пользователь не найден. Возможно, сессия истекла.');
        }

        // Определяем смену для списания упаковки
        $shift = null;

        if (in_array($user->role?->name, ShiftService::SHIFT_ROLES)) {
            // Для SHIFT_ROLES используем текущую смену пользователя
            $shift = $user->currentShift();
        } else {
            // Для NON_SHIFT_ROLES (admin, storekeeper) определяем смену через киоск
            $workshopId = session('kiosk_workshop_id');

            if (! $workshopId) {
                Log::channel('materials')->error(
                    'Не определён цех киоска для списания упаковки. '
                    .'Пользователь: '.$user->name.' (id: '.$user->id.', роль: '.$user->role?->name.'), '
                    .'товар #'.$marketplaceOrderItem->id.', '
                    .'заказ '.$marketplaceOrderItem->marketplaceOrder->order_id
                );

                return redirect()
                    ->back()
                    ->with('error', 'Не определён цех киоска. Обратитесь к администратору.');
            }

            // Получаем запланированную смену для цеха сегодня
            $schedule = ShiftSchedule::query()
                ->where('date', Carbon::today()->toDateString())
                ->where('workshop_id', $workshopId)
                ->first();

            if (! $schedule) {
                Log::channel('materials')->error(
                    'На сегодня нет смены в цеху киоска (выходной или не заполнено расписание). '
                    .'Цех: '.$workshopId.', '
                    .'пользователь: '.$user->name.' (id: '.$user->id.', роль: '.$user->role?->name.'), '
                    .'товар #'.$marketplaceOrderItem->id.', '
                    .'заказ '.$marketplaceOrderItem->marketplaceOrder->order_id
                );

                return redirect()
                    ->back()
                    ->with('error', 'На сегодня нет смены в этом цехе (выходной или не заполнено расписание). Обратитесь к администратору.');
            }

            if ($schedule->shift_id === null) {
                Log::channel('materials')->error(
                    'Сегодня в цехе киоска выходной. '
                    .'Цех: '.$workshopId.', '
                    .'пользователь: '.$user->name.' (id: '.$user->id.', роль: '.$user->role?->name.'), '
                    .'товар #'.$marketplaceOrderItem->id.', '
                    .'заказ '.$marketplaceOrderItem->marketplaceOrder->order_id
                );

                return redirect()
                    ->back()
                    ->with('error', 'Сегодня в этом цехе выходной. Списание упаковки невозможно.');
            }

            $shift = $schedule->shift;
        }

        // Продолжаем проверку упаковочных материалов
        $packagingConsumptions = $marketplaceOrderItem->item->consumption()
            ->whereHas('material', fn ($q) => $q->where('type_id', 3))
            ->with('material')
            ->get();

        $packagingRolls = [];
        if ($packagingConsumptions->isNotEmpty()) {
            if (! $shift) {
                Log::channel('materials')->error(
                    'Не удалось определить смену для списания упаковки. '
                    .'Пользователь: '.$user->name.' (id: '.$user->id.', роль: '.$user->role?->name.'), '
                    .'товар #'.$marketplaceOrderItem->id.', '
                    .'заказ '.$marketplaceOrderItem->marketplaceOrder->order_id
                );

                return redirect()
                    ->back()
                    ->with('error', 'Не удалось определить вашу смену для списания упаковочных материалов. Обратитесь к администратору.');
            }

            foreach ($packagingConsumptions as $consumption) {
                $roll = Roll::query()
                    ->where('material_id', $consumption->material_id)
                    ->where('status', Roll::STATUS_IN_WORKSHOP)
                    ->where('shift_id', $shift->id)
                    ->first();

                if (! $roll) {
                    return redirect()
                        ->back()
                        ->with('error', 'Нет рулона для "'.$consumption->material->title.'" в цехе');
                }

                $packagingRolls[$consumption->material_id] = $roll->id;
            }
        }

        Order::where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id)
            ->update([
                'status' => 3,
                'completed_at' => now(),
            ]);

        $marketplaceOrderItem->marketplaceOrder->status = 6;
        $marketplaceOrderItem->marketplaceOrder->completed_at = now();
        $marketplaceOrderItem->marketplaceOrder->save();

        $marketplaceOrderItem->otk_id = $user->id;
        $marketplaceOrderItem->packed_at = now();

        $marketplaceOrderItem->status = 3;
        $marketplaceOrderItem->completed_at = now();
        $marketplaceOrderItem->save();

        // Привязываем roll_id к существующим MovementMaterial для упаковки
        if (! empty($packagingRolls)) {
            $writeOffOrderIds = Order::query()
                ->where('type_movement', 3)
                ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id)
                ->pluck('id');

            foreach ($packagingRolls as $materialId => $rollId) {
                MovementMaterial::query()
                    ->whereIn('order_id', $writeOffOrderIds)
                    ->where('material_id', $materialId)
                    ->update(['roll_id' => $rollId]);
            }
        }

        $text = 'Сотрудник '.$user->name.' (id: '.$user->id.') выполнил заказ '
            .$marketplaceOrderItem->marketplaceOrder->order_id.
            ' (товар #'.$marketplaceOrderItem->id.')';
        Log::channel('items')->notice($text);

        // собираем новый url для редиректа
        $parsed = parse_url(url()->previous());

        $query = [];
        parse_str($parsed['query'] ?? '', $query);

        unset($query['scan_order_id']);

        $newUrl = $parsed['path'].(count($query) ? '?'.http_build_query($query) : '');

        return redirect($newUrl)->with('success', 'Заказ успешно выполнен');
    }

    public function cancel(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        $result = MarketplaceOrderItemService::cancelToSeamstress($marketplaceOrderItem);

        if (! $result['success']) {
            return redirect()
                ->back()
                ->with('error', $result['message']);
        }

        return redirect()
            ->back()
            ->with('success', $result['message']);
    }

    public function labeling(Request $request, MarketplaceOrderItem $marketplaceOrderItem, MarketplaceOrderItemService $marketplaceOrderItemService)
    {
        if (! $marketplaceOrderItemService->checkTimeoutOrderItem($marketplaceOrderItem)) {
            Log::channel('worker_limits')
                ->error('Швея '.$marketplaceOrderItem->seamstress->name.
                    ' пыталась сдать заказ в стикеровку почти сразу после начала работы!');

            return redirect()->back()
                ->with('error', 'Заказ на стикеровку не передан. Слишком быстро выполнен заказ.');
        }

        $fulfillmentType = $marketplaceOrderItem->marketplaceOrder->fulfillment_type;
        if ($fulfillmentType === 'FBS') {
            $orderId = $marketplaceOrderItem->marketplaceOrder->order_id;

            /** @var Sku|null $skuModel */
            $skuModel = $marketplaceOrderItem->item->sku()->first();
            $sku = $skuModel?->sku;

            $result = match ($marketplaceOrderItem->marketplaceOrder->marketplace_id) {
                1 => MarketplaceApiService::collectOrderOzon($orderId, $sku),
                2 => MarketplaceApiService::collectOrderWb($orderId),
                default => false,
            };

            if (! $result) {
                Log::channel('marketplace_api')
                    ->error('Не удалось передать заказ '.$orderId.' c sku: '.$sku.' на стикеровку');

                return redirect()->route('marketplace_order_items.index')
                    ->with('error', 'Не удалось передать заказ на стикеровку');
            }
        }

        $text = 'Швея '.$marketplaceOrderItem->seamstress->name.
            ' ('.$marketplaceOrderItem->seamstress->id.') передала товар #'.$marketplaceOrderItem->id.
            ' (заказ '.$marketplaceOrderItem->marketplaceOrder->order_id.') на стикеровку';

        Log::channel('items')->info($text);

        $marketplaceOrderItem->update([
            'status' => 5,
            'completed_at' => now(),
        ]);

        MarketplaceOrderItemService::resetClusterPriorityIfExhausted($marketplaceOrderItem->workshop_id);

        $this->updateRollIds($request, $marketplaceOrderItem);

        return redirect()->route('marketplace_order_items.index')
            ->with('success', 'Заказ передан на стикеровку');
    }

    public function getNewOrderItem()
    {
        $result = MarketplaceOrderItemService::getNewOrderItem();
        if ($result['success']) {
            return redirect()
                ->route('marketplace_order_items.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('marketplace_order_items.index')
            ->with('error', $result['message']);
    }

    public function fillEntireStack()
    {
        $result = MarketplaceOrderItemService::fillEntireStack();

        if ($result['success']) {
            return redirect()
                ->route('marketplace_order_items.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('marketplace_order_items.index')
            ->with('error', $result['message']);
    }

    public function completeCutting(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        Order::query()
            ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id)
            ->update([
                'status' => 3,
                'completed_at' => now(),
            ]);

        $marketplaceOrderItem->update([
            'status' => 8,
            'cutting_completed_at' => now(),
        ]);

        StackService::reduceStack($marketplaceOrderItem->cutter_id);

        $text = 'Закройщик '.$marketplaceOrderItem->cutter->name.
            ' ('.$marketplaceOrderItem->cutter->id.') выполнил заказ '.$marketplaceOrderItem->marketplaceOrder->order_id.
            ' (товар #'.$marketplaceOrderItem->id.')';
        Log::channel('items')->notice($text);

        $this->updateRollIds($request, $marketplaceOrderItem);

        return back()->with('success', 'Заказ успешно выполнен');
    }

    public function printCutting(MarketplaceOrderItemService $service)
    {
        $pdf = PDF::loadView('pdf.print_cutting', [
            'orders' => $service->getOrdersGroupedByMaterial(auth()->user()),
            'printQr' => Setting::getValue('print_qr_cutting', auth()->user()->currentWorkshop()?->id),
        ]);

        return $pdf->setPaper('A4')
            ->download('cutting.pdf');
    }

    private function updateRollIds(Request $request, MarketplaceOrderItem $marketplaceOrderItem): void
    {
        $rollIds = $request->input('roll_id', []);

        if (empty($rollIds)) {
            return;
        }

        $writeOffOrderIds = Order::query()
            ->where('type_movement', 3)
            ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id)
            ->pluck('id');

        if ($writeOffOrderIds->isEmpty()) {
            return;
        }

        foreach ($rollIds as $materialId => $rollId) {
            if ($rollId) {
                MovementMaterial::query()
                    ->whereIn('order_id', $writeOffOrderIds)
                    ->where('material_id', $materialId)
                    ->update(['roll_id' => $rollId]);
            }
        }
    }

    /**
     * Страница с формой для печати ленты стикеров.
     */
    public function stickerTapeForm()
    {
        $cutters = User::whereHas('role', fn ($q) => $q->where('name', 'cutter'))->get();
        $seamstresses = User::whereHas('role', fn ($q) => $q->where('name', 'seamstress'))->get();
        $items = MarketplaceItem::query()->get();

        $warehouses = MarketplaceWarehouse::query()
            ->orderBy('name')
            ->get()
            ->groupBy('marketplace_id')
            ->map(fn ($group) => $group->pluck('name', 'name')->toArray())
            ->toArray();

        return view('marketplace_order_items.sticker_tape_form', [
            'title' => 'Печать ленты стикеров',
            'cutters' => $cutters,
            'seamstresses' => $seamstresses,
            'items' => $items,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Генерирует PDF-стикер по данным из формы.
     */
    public function generateStickerTape(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:marketplace_items,id',
            'marketplace_id' => 'required|in:1,2',
            'cluster' => 'nullable|string',
            'cutter_id' => 'nullable|exists:users,id',
            'seamstress_id' => 'nullable|exists:users,id',
        ]);

        $item = MarketplaceItem::query()->findOrFail($validated['item_id']);

        $sku = Sku::query()
            ->where('item_id', $item->id)
            ->where('marketplace_id', $validated['marketplace_id'])
            ->firstOrFail();

        $barcode = ($validated['marketplace_id'] == 1)
            ? MarketplaceApiService::getBarcodeOzonBySku($sku->sku)
            : $sku->sku;

        $productSticker = ProductSticker::query()
            ->where('title', $item->title)
            ->first();

        $template = StickerService::resolveTemplate($item->title, $validated['marketplace_id']);

        $stickers = [[
            'barcode' => $barcode,
            'item' => $item,
            'order' => (object) [
                'order_id' => '',
                'cluster' => $validated['cluster'],
            ],
            'fontSizeCluster' => StickerService::resolveFontSizeCluster($validated['cluster'], $template),
            'seamstressId' => $validated['seamstress_id'],
            'cutterId' => $validated['cutter_id'],
            'article' => ($validated['marketplace_id'] == 2)
                ? MarketplaceApiService::getItemWbBySku($sku->sku)->nmID ?? ''
                : '',
            'color' => $productSticker?->color ?? '',
            'country' => $productSticker?->country ?? '',
            'material' => $productSticker?->material ?? '',
            'fastening_type' => $productSticker?->fastening_type ?? '',
            'marketplace_id' => $validated['marketplace_id'],
        ]];

        $pdf = Pdf::loadView($template, ['stickers' => $stickers]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('sticker.pdf');
    }

    /**
     * Страница импорта стикеров из Excel.
     */
    public function stickerTapeImport()
    {
        return view('marketplace_order_items.sticker_tape_import', [
            'title' => 'Импорт стикеров из Excel',
        ]);
    }
}
