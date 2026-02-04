<?php

namespace App\Http\Controllers;

use App\Jobs\SendTelegramMessageJob;
use App\Models\MarketplaceOrderItem;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use App\Models\Setting;
use App\Models\User;
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
        if ($request->filled('barcode')) {
            $user = UserService::getUserByBarcode($request->barcode);

            if (! $user) {
                return redirect()
                    ->route('sticker_printing')
                    ->with('error', 'Пользователь не найден');
            }

            $query = $request->except('barcode');
            $query['user_id'] = $user->id;

            return redirect()->route('sticker_printing', $query);
        }

        $workShift = [];
        if ($request->filled('user_id')) {
            $user = User::query()->find($request->user_id);

            $workShift = [
                'shift_is_open' => $user->shift_is_open,
                'start' => $user->actual_start_work_shift,
                'end' => $user->end_work_shift,
            ];
        }

        return view('sticker_printing', [
            'title' => 'Печать стикеров',
            'userId' => $request->user_id ?? 0,
            'user' => $user ?? null,
            'items' => MarketplaceOrderItemService::getItemsForLabeling($request),
            'users' => User::query()->whereIn('role_id', [1, 2, 4, 5])
                ->where('name', 'not like', '%Тест%')
                ->orderBy('name')
                ->get(),
            'workShift' => $workShift,
        ]);
    }

    public function openCloseWorkShift(Request $request)
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

            if ($user->end_work_shift->greaterThan(now())) {
                Log::channel('work_shift')
                    ->error('Внимание! Сотрудник '.$selectedUser->name.' ('.$selectedUser->id.') '.
                        'пытался закрыть смену до окончания рабочего времени.');

                return redirect()
                    ->route('opening_closing_shifts')
                    ->with('error', 'Ошибка! Нельзя закрыть смену, пока не закончилось рабочее время!');
            }

            if ($this->hasOrdersInWork($user)) {
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

    public function openCloseWorkShiftAdmin(User $user)
    {
        if ($user->shift_is_open) {
            if ($this->hasOrdersInWork($user)) {
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
                'status' => 0,
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

        $text = 'Сотрудник '.auth()->user()->name.' указал брак: '."\n".$list;

        Log::channel('erp')
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

    private function hasOrdersInWork(User $user): bool
    {
        if ($user->isSeamstress()) {
            return MarketplaceOrderItem::query()
                ->where('seamstress_id', $user->id)
                ->where('status', 4)
                ->exists();
        }

        if ($user->isCutter()) {
            return MarketplaceOrderItem::query()
                ->where('cutter_id', $user->id)
                ->where('status', 7)
                ->exists();
        }

        return false;
    }
}
