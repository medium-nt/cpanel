<?php

namespace App\Http\Controllers;

use App\Jobs\SendTelegramMessageJob;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\ProductSticker;
use App\Models\Roll;
use App\Models\Setting;
use App\Models\Sku;
use App\Models\User;
use App\Services\KioskService;
use App\Services\MarketplaceApiService;
use App\Services\MarketplaceOrderItemService;
use App\Services\ScheduleService;
use App\Services\UserService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StickerPrintingController extends Controller
{
    public function index(Request $request)
    {
        $user = User::query()->find(session('user_id'));

        if (! $user) {
            return redirect()
                ->route('kiosk');
        }

        return view('sticker_printing', [
            'title' => 'Печать стикеров',
            'userId' => $request->user_id ?? 0,
            'user' => $user,
            'items' => MarketplaceOrderItemService::getItemsForLabeling($request),
            'isOtkOnShift' => User::query()->where('role_id', 5)
                ->where('shift_is_open', true)
                ->exists(),
            'seamstresses' => User::query()->where('role_id', 1)
                ->where('name', 'not like', '%Тест%')
                ->orderBy('name')
                ->get(),
            'canUseFilter' => KioskService::canUseFilter($user),
            'canSticking' => KioskService::canSticking($user),
        ]);
    }

    public function openCloseWorkShift(Request $request, KioskService $kioskService)
    {
        $selectedUser = $request->user_id
            ? User::query()->find($request->user_id)
            : null;

        if (! $selectedUser) {
            Log::channel('work_shift')
                ->error('Ошибка! Id сотрудника не передан в запросе со штрих-кодом: '
                    .($request->barcode ?? ' --- '));

            return redirect()
                ->route('opening_closing_shifts')
                ->with('error', 'Внутренняя ошибка!');
        }

        if (! $request->filled('barcode')) {
            Log::channel('work_shift')
                ->error('Внимание! Сотрудник '.$selectedUser->name.' ('.$selectedUser->id.') '
                    .'не отсканировал штрихкод (штрихкод отсутствует).');

            return redirect()
                ->route('opening_closing_shifts')
                ->with('error', 'Ошибка! Штрихкод не отсканирован!');
        }

        $user = UserService::getUserByBarcode($request->barcode);

        if (! $user) {
            Log::channel('work_shift')
                ->error('Внимание! Сотрудник '.$selectedUser->name.' ('.$selectedUser->id.') '
                    .'отсканировал неверный штрихкод: '.$request->barcode);

            return redirect()
                ->route('opening_closing_shifts')
                ->with('error', 'Штрихкод неверен! Такой сотрудник в системе не найден.');
        }

        if ($selectedUser->id != $user->id) {
            Log::channel('work_shift')
                ->error('Внимание! Сотрудник '.$selectedUser->name.' ('.$selectedUser->id.') '.
                    'пытался закрыть смену сотрудника '.$user->name.' ('.$user->id.') ');

            return redirect()
                ->route('opening_closing_shifts')
                ->with('error', 'Ошибка! Штрихкод не соответствует выбранному сотруднику.');
        }

        if ($user->shift_is_open) {

            if ($user->end_work_shift->greaterThan(now()) && ! $user->dailyLimitReached()) {
                Log::channel('work_shift')
                    ->error('Внимание! Сотрудник '.$selectedUser->name.' ('.$selectedUser->id.') '.
                        'пытался закрыть смену до окончания рабочего времени.');

                return redirect()
                    ->route('opening_closing_shifts')
                    ->with('error', 'Ошибка! Нельзя закрыть смену, пока не закончилось рабочее время!');
            }

            if ($kioskService->hasOrdersInWork($user)) {
                Log::channel('work_shift')
                    ->error('Внимание! Сотрудник '.$selectedUser->name.' ('.$selectedUser->id.') '.
                        'пытался закрыть смену, но есть заказы в работе.');

                return redirect()
                    ->route('opening_closing_shifts')
                    ->with('error', 'Ошибка! Нельзя закрыть смену, пока есть заказы в работе!');
            }

            UserService::checkWorkShiftClosure($user);

            $user->shift_is_open = false;
            $user->actual_start_work_shift = '00:00:00';
            $user->closed_work_shift = now()->format('H:i');

            ScheduleService::closeWorkShift($user);
        } else {
            if (UserService::isSecondShiftOpeningToday($user)) {
                Log::channel('work_shift')
                    ->error('Внимание! Сотрудник '.$selectedUser->name.' ('.$selectedUser->id.') '.
                        'пытался второй раз за день открыть смену.');

                return redirect()
                    ->route('opening_closing_shifts')
                    ->with('error', 'Ошибка! Нельзя второй раз за день открыть смену!');
            }

            UserService::checkLateStartWorkShift($user);

            $user->shift_is_open = true;
            $user->actual_start_work_shift = now()->format('H:i');
            ScheduleService::openWorkShift($user);
        }

        $user->save();

        Log::channel('work_shift')
            ->info('Сотрудник '.$user->name.' ('.$user->id.') '
                .($user->shift_is_open ? 'открыл' : 'закрыл').' смену.');

        $route = $user->shift_is_open ? 'opening_closing_shifts' : 'kiosk';

        return redirect()
            ->route($route)
            ->with('success', 'Ваша смена успешно '.($user->shift_is_open ? 'открыта' : 'закрыта'));
    }

    public function openCloseWorkShiftAdmin(User $user, KioskService $kioskService)
    {
        if ($user->shift_is_open) {
            if ($kioskService->hasOrdersInWork($user)) {
                return redirect()
                    ->route('home')
                    ->with('error', 'Ошибка! Нельзя закрыть смену, пока есть заказы в работе!');
            }

            $user->closed_work_shift = now()->format('H:i');
            ScheduleService::closeWorkShift($user);
        } else {
            ScheduleService::openWorkShift($user);
        }

        $user->shift_is_open = ! $user->shift_is_open;
        $user->actual_start_work_shift = ($user->shift_is_open ? now()->format('H:i') : '00:00:00');
        $user->save();

        Log::channel('work_shift')
            ->info('Админ '.($user->shift_is_open ? 'открыл' : 'закрыл').
                ' смену сотруднику '.$user->name.' ('.$user->id.') ');

        return redirect()
            ->route('home')
            ->with('success', 'Смена успешно '.($user->shift_is_open ? 'открыта' : 'закрыта'));
    }

    public function kiosk(Request $request)
    {
        // 1. Idle → сбрасываем пользователя
        if ($request->boolean('idle')) {
            session()->forget('user_id');
            $user = null;
        } // 2. Если есть barcode → ищем пользователя по нему
        elseif ($request->filled('barcode')) {
            $user = UserService::getUserByBarcode($request->barcode);

            if ($user) {
                session(['user_id' => $user->id]);
            }
        } // 3. Иначе → пробуем восстановить из сессии
        else {
            $user = User::find(session('user_id'));
        }

        return view('kiosk.kiosk', [
            'title' => 'Киоск',
            'user' => $user,
        ]);
    }

    public function opening_closing_shifts(Request $request)
    {
        $user = User::query()->find(session('user_id'));

        if (! $user) {
            return redirect()
                ->route('kiosk');
        }

        $shiftStart = Carbon::parse($user->start_work_shift);
        $allowedTime = $shiftStart->copy()->addMinutes($user->max_late_minutes);
        $lateAfterAllowed = $allowedTime->diffInMinutes(now(), false);
        $lateFromShiftStart = $shiftStart->diffInSeconds(now(), false);

        return view('kiosk.opening_closing_shifts', [
            'title' => 'Открытие/закрытие смены',
            'user' => $user,
            'lateOpenedShiftPenalty' => Setting::getValue('late_opened_shift_penalty'),
            'isLate' => $lateAfterAllowed > 0,
            'lateTimeStartWorkShift' => max(floor($lateFromShiftStart / 60), 0),
        ]);
    }

    public function statisticsReports(Request $request)
    {
        $daysAgo = $request->input('days_ago') ?? 0;
        $daysAgo = intval($daysAgo);

        if ($daysAgo < 0 || $daysAgo > 28) {
            $daysAgo = 0;
        }

        $dates = MarketplaceOrderItemService::getDatesByLargeSizeRating($daysAgo);

        return view('kiosk.statistics_reports', [
            'title' => 'Статистика/Отчеты',
            'userId' => $request->user_id ?? 0,
            'dates' => json_encode($dates),
            'seamstressesJson' => json_encode(MarketplaceOrderItemService::getSeamstressesLargeSizeRating($dates)),
            'days_ago' => $daysAgo,
        ]);
    }

    public function productStickers()
    {
        $user = User::find(session('user_id'));

        if (! $user) {
            return redirect()
                ->route('kiosk');
        }

        return view('kiosk.product_stickers', [
            'title' => 'Печать стикеров товара',
            'user' => $user,
            'titleMaterials' => ProductSticker::query()->orderBy('title')->get(),
        ]);
    }

    public function defects()
    {
        $user = User::find(session('user_id'));

        if (! $user || ! $user->isSeamstress() && ! $user->isCutter()) {
            return redirect()
                ->route('kiosk');
        }

        $field = match ($user->role->name) {
            'seamstress' => 'seamstress_id',
            'cutter' => 'cutter_id',
            default => null,
        };

        $defectMaterialOrders = null;

        if ($field) {
            $defectMaterialOrders = Order::query()
                ->where($field, session('user_id'))
                ->where('type_movement', 4)
                ->whereDate('created_at', today())
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('kiosk.defects', [
            'title' => 'Брак / Остатки',
            'userId' => session('user_id'),
            'isAdded' => false,
            'defectMaterialOrders' => $defectMaterialOrders,
        ]);
    }

    public function saveDefects(Request $request)
    {
        $rules = [
            'user_id' => 'required|exists:users,id',
            'roll' => 'required|exists:rolls,roll_code',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string',
        ];

        $text = [
            'user_id.required' => 'Системная ошибка! Не указан пользователь.',
            'user_id.exists' => 'Системная ошибка! Не верный пользователь.',
            'roll.required' => 'Поле стикер рулона не заполнено',
            'roll.exists' => 'Такой рулон в системе не найден',
            'quantity.required' => 'Поле количество не заполнено',
            'quantity.numeric' => 'Поле количество должно быть числом',
            'quantity.min' => 'Поле количество должно быть больше 0',
            'reason.string' => 'Поле комментарий должно быть строкой',
        ];

        $validatedData = $request->validate($rules, $text);

        $quantity = $validatedData['quantity'];
        $user = User::find($validatedData['user_id']);
        $roll = Roll::where('roll_code', $validatedData['roll'])->first();
        $comment = $validatedData['reason'] ?? '';

        if (! $user || ! $user->isSeamstress() && ! $user->isCutter()) {
            return redirect()
                ->route('kiosk');
        }

        if ($roll == null || $quantity == 0) {
            return redirect()
                ->route('defects.create')
                ->with('error', 'Введите данные');
        }

        $field = match ($user->role->name) {
            'seamstress' => 'seamstress_id',
            'cutter' => 'cutter_id',
            default => throw new \Exception('Недопустимая роль: '.$user->role->name),
        };

        try {
            DB::beginTransaction();

            $order = Order::query()->create([
                $field => $user->id,
                'type_movement' => 4,
                'status' => 1,
                'comment' => $comment,
                'completed_at' => now(),
            ]);

            $movementMaterial = MovementMaterial::query()->create([
                'order_id' => $order->id,
                'material_id' => $roll->material->id,
                'quantity' => $quantity,
                'roll_id' => $roll->id,
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return back()
                ->with('error', 'Внутренняя ошибка');
        }

        $list = '• '.$movementMaterial->material->title.' '.$movementMaterial->quantity.' '.$movementMaterial->material->unit."\n";

        $text = 'Сотрудник '.$user->name.' указал брак: '."\n".$list;

        Log::channel('tg')
            ->notice('Отправляем сообщение в ТГ админу и работающим кладовщикам: '.$text);

        SendTelegramMessageJob::dispatch(config('telegram.admin_id'), $text);

        foreach (UserService::getListStorekeepersWorkingToday() as $index => $tgId) {
            SendTelegramMessageJob::dispatch($tgId, $text)
                ->delay(now()->addSeconds($index + 1));
        }

        $defectMaterialOrders = Order::query()
            ->where($field, session('user_id'))
            ->where('type_movement', 4)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('kiosk.defects', [
            'title' => 'Брак / Остатки',
            'isAdded' => true,
            'userId' => session('user_id'),
            'defectMaterialOrders' => $defectMaterialOrders,
        ]);
    }

    public function getRollByCode(string $roll_code): JsonResponse
    {
        $roll = Roll::where('roll_code', $roll_code)->first();

        if (! $roll) {
            return response()->json(['material_id' => null]);
        }

        return response()->json([
            'material_id' => $roll->material->title,
        ]);
    }

    public function printSticker(Order $order)
    {
        $pdf = PDF::loadView('pdf.defect_sticker', [
            'order' => $order,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('barcode.pdf');
    }

    public function printProductLabel(string $material)
    {
        $sticker = ProductSticker::query()
            ->where('title', $material)
            ->first();

        if (! $sticker) {
            echo 'Стикер не найден. Создайте его в админке.';
            exit;
        }

        $data = [
            'title' => $sticker->title,
            'color' => $sticker->color,
            'print_type' => $sticker->print_type,
            'material' => $sticker->material,
            'country' => $sticker->country,
            'fastening_type' => $sticker->fastening_type,
        ];

        $pdf = PDF::loadView('pdf.product_label', [
            'data' => $data,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('product_label.pdf');
    }

    public function returns(Request $request, KioskService $kioskService)
    {
        $kioskService->authorizeOtk();

        return view('kiosk.returns', [
            'title' => 'Товары готовые к осмотру',
            'userId' => session('user_id'),
            'returnItems' => $kioskService->getFilteredInspectionItems($request, 10),
        ]);
    }

    public function onInspection(Request $request, KioskService $kioskService)
    {
        $kioskService->authorizeOtk();

        return view('kiosk.on_inspection', [
            'title' => 'Товары на осмотре',
            'userId' => session('user_id'),
            'onInspectionItems' => $kioskService->getFilteredInspectionItems($request, 12),
        ]);
    }

    public function processedItems(Request $request, KioskService $kioskService)
    {
        $kioskService->authorizeOtk();

        return view('kiosk.processed_items', [
            'title' => 'Обработанные товары',
            'userId' => session('user_id'),
            'processedItems' => $kioskService->getFilteredInspectionItems($request, [15, 16], orderByDesc: true),
        ]);
    }

    public function scanInspectionItem(Request $request): JsonResponse
    {
        $barcode = $request->input('barcode');

        if (! $barcode) {
            return response()->json(['success' => false, 'message' => 'Штрихкод не передан'], 400);
        }

        // Обработка OZON FBS (15 символов)
        if (! is_array($barcode) && mb_strlen(trim($barcode)) == 15) {
            $barcode = MarketplaceApiService::getOzonPostingNumberByBarcode($barcode);
        }

        // Обработка OZON возврат (начинается с 'ii')
        if (! is_array($barcode) && str_starts_with(trim($barcode), 'ii')) {
            $barcode = MarketplaceApiService::getOzonPostingNumberByReturnBarcode($barcode);
        }

        $isFBO = false;
        $fboMarketplaceId = null;

        // Обработка WB FBO (13 символов)
        if (! is_array($barcode) && mb_strlen(trim($barcode)) == 13) {
            $sku = trim($barcode);
            $barcode = Sku::query()->where('sku', $sku)->first()?->item->id ?? '-';
            $isFBO = true;
            $fboMarketplaceId = 2; // WB
        }

        // Обработка OZON FBO (начинается с 'OZN')
        if (! is_array($barcode) && str_starts_with(trim($barcode), 'OZN')) {
            $sku = trim($barcode, 'OZN');
            $barcode = Sku::query()->where('sku', $sku)->first()?->item->id ?? '-';
            $isFBO = true;
            $fboMarketplaceId = 1; // Ozon
        }

        // Ищем item по нескольким полям (как в warehouse_of_item/new_refunds)
        // товар должен быть в статусе 10
        $orderItem = MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
            ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
            ->where('marketplace_order_items.status', 10) // На разборе
            ->where(function ($query) use ($barcode) {
                $query->where('marketplace_orders.order_id', $barcode)
                    ->orWhere('marketplace_order_items.storage_barcode', $barcode)
                    ->orWhere('marketplace_orders.part_b', $barcode)
                    ->orWhere('marketplace_orders.barcode', $barcode)
                    ->orWhere('marketplace_items.id', $barcode);
            })
            ->when($isFBO, function ($query) use ($fboMarketplaceId) {
                $query->where('marketplace_orders.fulfillment_type', 'FBO')
                    ->where('marketplace_orders.marketplace_id', $fboMarketplaceId);
            })
            ->select('marketplace_order_items.*')
            ->first();

        if (! $orderItem) {
            return response()->json(['success' => false, 'message' => 'Товар не найден'], 404);
        }

        if ($orderItem->status != 10) {
            if ($orderItem->status == 12) {
                return response()->json(['success' => false, 'message' => 'Товар уже добавлен на проверку'], 400);
            }

            return response()->json(['success' => false, 'message' => 'Товар не имеет статус "На разборе"'], 400);
        }

        // Проверяем лимит товаров в статусе 12 (На проверке) - максимум 5
        $inspectionCount = MarketplaceOrderItem::query()
            ->where('status', 12)
            ->count();

        if ($inspectionCount >= 5) {
            return response()->json(['success' => false, 'message' => 'Достигнут лимит 5 товаров на проверке'], 400);
        }

        // Меняем статус товара на 12 (На проверке)
        $orderItem->status = 12;
        $orderItem->save();

        Log::channel('items')
            ->info('Упаковщик '.session('user_id').' взял товар id: '.$orderItem->id.
                ' от заказа '.$orderItem->marketplace_order_id.' на проверку');

        return response()->json(['success' => true, 'message' => 'Статус обновлён']);
    }

    public function itemCard(Request $request, int $item_id, string $action)
    {
        $user = User::find(session('user_id'));

        if (! $user || ! $user->isOtk()) {
            return redirect()->route('kiosk');
        }

        $orderItem = MarketplaceOrderItem::query()->find($item_id);

        if (! $orderItem) {
            return redirect()->route('on_inspection')
                ->with('error', 'Товар не найден');
        }

        // Проверяем допустимость action
        if (! in_array($action, ['repack', 'replace', 'defect'])) {
            return redirect()->route('on_inspection')
                ->with('error', 'Неверное действие');
        }

        $title = match ($action) {
            'repack' => 'Переупаковка',
            'replace' => 'Подмена',
            'defect' => 'Брак',
        };

        return view('kiosk.item_card', [
            'title' => $title,
            'orderItem' => $orderItem,
            'action' => $action,
        ]);
    }

    public function processDefect(Request $request, int $item_id)
    {
        $user = User::find(session('user_id'));

        if (! $user || ! $user->isOtk()) {
            return redirect()->route('kiosk');
        }

        $orderItem = MarketplaceOrderItem::query()->find($item_id);

        if (! $orderItem) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Товар не найден');
        }

        $request->validate([
            'reason' => 'required|in:Порван,Грязный,Неверный размер,Брак ткани',
        ], [
            'reason.required' => 'Выберите причину брака',
            'reason.in' => 'Неверная причина',
        ]);

        $orderItem->update([
            'defect_reason' => $request->reason,
            'status' => 16,
            'otk_id' => $user->id,
        ]);

        Log::channel('items')
            ->info('Упаковщик '.session('user_id').' отправил товар id: '.$orderItem->id.
                ' от заказа '.$orderItem->marketplace_order_id.' в брак');

        return redirect()
            ->route('on_inspection')
            ->with('success', 'Товар отправлен на утилизацию');
    }

    public function processRepack(Request $request, MarketplaceOrderItem $orderItem, KioskService $kioskService)
    {
        $user = User::find(session('user_id'));

        if (! $user || ! $user->isOtk()) {
            return redirect()->route('kiosk');
        }

        $request->validate([
            'material_used' => 'required|in:nothing,flyer,bag,flyer-bag',
        ], [
            'material_used.required' => 'Выберите потраченный материал',
            'material_used.in' => 'Неверное значение',
        ]);

        // проверить что материалы есть
        if (! $kioskService->hasPackagingMaterials($orderItem->item, $request->material_used)) {
            return redirect()
                ->route('kiosk.item_card', ['item_id' => $orderItem->id, 'action' => 'repack'])
                ->withInput()
                ->with('error', 'Недостаточно материала в цехе для переупаковки');
        }

        try {
            DB::beginTransaction();

            // Списываем материалы упаковки
            $kioskService->deductPackagingMaterials(
                $orderItem->item,
                $request->material_used,
                'Переупаковка товара No: '.$orderItem->id.
                ' ('.$orderItem->item->title.' '.$orderItem->item->width.'х'.$orderItem->item->height.')'
            );

            $orderItem->update([
                'status' => 15,
                'repacker_id' => $user->id,
                'repacked_at' => now(),
            ]);

            Log::channel('items')
                ->info('Упаковщик '.session('user_id').' переупаковал товар id: '.$orderItem->id);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::channel('items')->error('Ошибка при переупаковке товара: '.$e->getMessage());

            return redirect()
                ->route('on_inspection')
                ->with('error', 'Ошибка при переупаковке товара');
        }

        return redirect()
            ->route('on_inspection')
            ->with('success', 'Товар осмотрен');
    }

    public function processReplace(Request $request, MarketplaceOrderItem $orderItem, KioskService $kioskService): JsonResponse
    {
        $user = User::find(session('user_id'));

        if (! $user || ! $user->isOtk()) {
            return response()->json(['success' => false, 'message' => 'Нет доступа'], 403);
        }

        $request->validate([
            'material_title' => 'required|string',
            'width' => 'required|integer',
            'height' => 'required|integer',
            'material_used' => 'required|in:nothing,flyer,bag,flyer-bag',
        ]);

        // Находим существующий MarketplaceItem для проверки материалов
        $item = MarketplaceItem::query()
            ->where('title', $request->material_title)
            ->where('width', $request->width)
            ->where('height', $request->height)
            ->first();

        if (! $item) {
            return response()->json(['success' => false, 'message' => 'Товар с такими параметрами не найден'], 404);
        }

        // Проверяем что материалы есть в цеху
        if (! $kioskService->hasPackagingMaterials($item, $request->material_used)) {
            return response()->json(['success' => false, 'message' => 'Недостаточно материала в цехе для подмены'], 400);
        }

        try {
            DB::beginTransaction();

            // 1. Помечаем старый товар как "утерян" (статус 14)
            $orderItem->update(['status' => 14]);

            // 3. Создаём новый MarketplaceOrder (FBO)
            $marketplaceOrder = MarketplaceOrder::query()->create([
                'order_id' => '...',
                'marketplace_id' => 1,
                'fulfillment_type' => 'FBO',
                'status' => 9,
                'completed_at' => now(),
                'returned_at' => now(),
                'created_at' => now(),
            ]);

            // 4. Создаём новый MarketplaceOrderItem
            $newOrderItem = MarketplaceOrderItem::query()->create([
                'marketplace_order_id' => $marketplaceOrder->id,
                'marketplace_item_id' => $item->id,
                'shelf_id' => null,
                'quantity' => 1,
                'price' => 0,
                'status' => 15,
                'seamstress_id' => 0,
                'cutter_id' => null,
                'otk_id' => null,
                'repacker_id' => $user->id,
                'repacked_at' => now(),
                'completed_at' => now()->startOfDay()->subDays(2),
                'created_at' => Carbon::parse($marketplaceOrder->created_at),
            ]);

            $marketplaceOrder->order_id = 'REPLACE-'.$orderItem->marketplaceOrder->order_id;
            $marketplaceOrder->save();

            // 5. Списание материалов упаковки
            $kioskService->deductPackagingMaterials(
                $item,
                $request->material_used,
                'Подмена товара No: '.$orderItem->id.' на новый '.$newOrderItem->id.
                ' ('.$item->title.' '.$item->width.'х'.$item->height.')'
            );

            DB::commit();

            Log::channel('items')
                ->info('Упаковщик '.session('user_id').' подменил товар id: '.$orderItem->id.
                    ' (заказ '.$orderItem->marketplaceOrder->order_id.') на '.
                    $newOrderItem->id.' (заказ '.$marketplaceOrder->order_id.')');

            return response()->json([
                'success' => true,
                'marketplace_order_id' => $marketplaceOrder->order_id,
                'item_id' => $newOrderItem->id,
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::channel('items')->error('Ошибка при подмене товара: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Внутренняя ошибка'], 500);
        }
    }
}
