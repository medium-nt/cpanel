<?php

namespace App\Http\Controllers;

use App\Models\Roll;
use App\Models\User;
use App\Services\DefectMaterialService;
use App\Services\MarketplaceOrderItemService;
use App\Services\ScheduleService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $daysAgo = $request->input('days_ago') ?? 0;
        $daysAgo = intval($daysAgo);

        if ($daysAgo < 0 || $daysAgo > 28) {
            $daysAgo = 0;
        }

        $dates = MarketplaceOrderItemService::getDatesByLargeSizeRating($daysAgo);

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
            'items' => MarketplaceOrderItemService::getItemsForLabeling($request),
            'users' => User::query()->whereIn('role_id', [1, 2, 4, 5])
                ->where('name', 'not like', '%Тест%')
                ->orderBy('name')
                ->get(),
            'dates' => json_encode($dates),
            'seamstressesJson' => json_encode(MarketplaceOrderItemService::getSeamstressesLargeSizeRating($dates)),
            'days_ago' => $daysAgo,
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
                ->route('sticker_printing')
                ->with('error', 'Внутренняя ошибка!');
        }

        if (! $request->filled('barcode')) {
            Log::channel('work_shift')
                ->error('Внимание! Сотрудник '.$selectedUser->name.' ('.$selectedUser->id.') '
                    .'не отсканировал штрихкод (штрихкод отсутствует).');

            return redirect()
                ->route('sticker_printing')
                ->with('error', 'Ошибка! Штрихкод не отсканирован!');
        }

        $user = UserService::getUserByBarcode($request->barcode);

        if (! $user) {
            Log::channel('work_shift')
                ->error('Внимание! Сотрудник '.$selectedUser->name.' ('.$selectedUser->id.') '
                    .'отсканировал неверный штрихкод: '.$request->barcode);

            return redirect()
                ->route('sticker_printing', ['user_id' => $selectedUser->id])
                ->with('error', 'Штрихкод неверен! Такой сотрудник в системе не найден.');
        }

        if ($selectedUser->id != $user->id) {
            Log::channel('work_shift')
                ->error('Внимание! Сотрудник '.$selectedUser->name.' ('.$selectedUser->id.') '.
                    'пытался закрыть смену сотрудника '.$user->name.' ('.$user->id.') ');

            return redirect()
                ->route('sticker_printing', ['user_id' => $selectedUser->id])
                ->with('error', 'Ошибка! Штрихкод не соответствует выбранному сотруднику.');
        }

        if ($user->shift_is_open) {

            if ($user->end_work_shift->greaterThan(now())) {
                Log::channel('work_shift')
                    ->error('Внимание! Сотрудник '.$selectedUser->name.' ('.$selectedUser->id.') '.
                        'пытался закрыть смену до окончания рабочего времени.');

                return redirect()
                    ->route('sticker_printing', ['user_id' => $selectedUser->id])
                    ->with('error', 'Ошибка! Нельзя закрыть смену, пока не закончилось рабочее время!');
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
                    ->route('sticker_printing', ['user_id' => $selectedUser->id])
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

        return redirect()
            ->route('sticker_printing', ['user_id' => $selectedUser->id])
            ->with('success', 'Смена успешно '.($user->shift_is_open ? 'открыта' : 'закрыта'));
    }

    public function openCloseWorkShiftAdmin(User $user)
    {
        if ($user->shift_is_open) {
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
        if ($request->filled('idle') && $request->input('idle') == '1') {
            session()->forget('user_id');
        }

        $user = null;
        if ($request->filled('barcode')) {
            $user = UserService::getUserByBarcode($request->barcode);
        }

        if (session('user_id')) {
            $user = User::query()->find(session('user_id'));
        }

        if ($user !== null) {
            session(['user_id' => $user->id]);
        }

        return view('kiosk.kiosk', [
            'title' => 'Киоск',
            'user' => $user,
        ]);
    }

    public function opening_closing_shifts(Request $request)
    {
        $user = null;
        if ($request->filled('barcode')) {
            $user = UserService::getUserByBarcode($request->barcode);
        }

        return view('kiosk.opening_closing_shifts', [
            'title' => 'Открытие/закрытие смены',
            'user' => $user,
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
        return view('kiosk.defects', [
            'title' => 'Брак / Остатки',
        ]);
    }

    public function saveDefects(Request $request)
    {
        dd($request->all());

        if (! DefectMaterialService::store($request)) {
            return back()
                ->with('error', 'Внутренняя ошибка');
        }

        return view('kiosk.defects', [
            'title' => 'Брак / Остатки',
            'success' => 'Данные успешно сохранены',
        ])->with('success', 'Брак добавлен');
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
}
