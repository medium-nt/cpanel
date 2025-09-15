<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MarketplaceOrderItemService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StickerPrintingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('barcode')) {
            $user = UserService::getUserByBarcode($request->barcode);

            if (!$user) {
                return redirect()
                    ->route('sticker_printing')
                    ->with('error', 'Пользователь не найден');
            }

            $query = $request->except('barcode');
            $query['seamstress_id'] = $user->id;

            return redirect()->route('sticker_printing', $query);
        }

        $daysAgo = $request->input('days_ago') ?? 0;
        $daysAgo = intval($daysAgo);

        if ($daysAgo < 0 || $daysAgo > 28) {
            $daysAgo = 0;
        }

        $dates = MarketplaceOrderItemService::getDatesByLargeSizeRating($daysAgo);

        $workShift = [];
        if ($request->filled('seamstress_id')) {
            $user = User::query()->find($request->seamstress_id);

            $workShift = [
                'shift_is_open' => $user->shift_is_open,
                'start' => $user->start_work_shift,
                'end' => Carbon::parse($user->start_work_shift)
                    ->copy()->addHours($user->number_working_hours),
            ];
        }

        return view('sticker_printing', [
            'title' => 'Печать стикеров',
            'seamstressId' => $request->seamstress_id ?? 0,
            'items' => MarketplaceOrderItemService::getItemsForLabeling($request),
            'seamstresses' => User::query()->where('role_id', '1')
                ->where('name', 'not like', '%Тест%')->get(),
            'dates' => json_encode($dates),
            'seamstressesJson' => json_encode(MarketplaceOrderItemService::getSeamstressesLargeSizeRating($dates)),
            'days_ago' => $daysAgo,
            'workShift' => $workShift,
        ]);
    }

    public function openCloseWorkShift(Request $request)
    {
        $userId = $request->seamstress_id ?? 0;

        if ($request->filled('barcode')) {
            $user = UserService::getUserByBarcode($request->barcode);

            if (!$user) {
                return redirect()
                    ->route('sticker_printing' , ['seamstress_id' => $userId])
                    ->with('error', 'Штрихкод неверен! Такой пользователь в системе не найден.');
            }

            if ($request->seamstress_id != $user->id) {
                return redirect()
                    ->route('sticker_printing' , ['seamstress_id' => $userId])
                    ->with('error', 'Ошибка! Штрихкод не соответствует выбранному пользователю.');
            }

            if ($user->shift_is_open) {
                $end = Carbon::parse($user->start_work_shift)
                    ->copy()->addHours($user->number_working_hours);

                if ($end->greaterThan(now())) {
                    return redirect()
                        ->route('sticker_printing', ['seamstress_id' => $userId])
                        ->with('error', 'Нельзя закрыть смену, пока не закончилось рабочее время!');
                }

                $user->shift_is_open = false;
            } else {
                $user->shift_is_open = true;
            }

            $user->save();

            return redirect()
                ->route('sticker_printing', ['seamstress_id' => $userId])
                ->with('success', 'Смена ' . ($user->shift_is_open ? 'открыта' : 'закрыта'));
        }

        return redirect()
            ->route('sticker_printing')
            ->with('error', 'Штрихкод неверен! Такой пользователь в системе не найден.');
    }
}
