<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceSupply;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workshop;
use App\Services\MarketplaceOrderItemService;
use App\Services\MarketplaceOrderService;
use App\Services\MovementMaterialToWorkshopService;
use App\Services\RollService;
use App\Services\ScheduleService;
use App\Services\ShiftService;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $employeeId = $request->input('employee_id') ?? $user->id;

        $daysAgo = $request->input('days_ago') ?? 0;
        $daysAgo = intval($daysAgo);

        if ($daysAgo < 0 || $daysAgo > 28) {
            $daysAgo = 0;
        }

        // Определяем scope цеха: null = все цеха, int = конкретный цех, 0 = нет цеха (нули)
        $workshopScope = null;
        if (! in_array($user->role->name, ['admin', 'storekeeper', 'manager'])) {
            $workshopScope = $user->currentWorkshop()?->id ?? 0;
        }

        $gazelkaShipments = MarketplaceSupply::query()
            ->whereNotNull('gazelka_shipment_date')
            ->where('gazelka_shipment_date', '>=', now()->startOfDay())
            ->where('gazelka_shipment_date', '<=', now()->addDays(3)->endOfDay())
            ->orderBy('gazelka_shipment_date')
            ->get();

        $dates = MarketplaceOrderItemService::getDatesByLargeSizeRating($daysAgo);

        $missingScheduleDates = $user->isAdmin()
            ? ShiftService::getMissingScheduleDates()
            : [];

        // Непроставленные даты по цехам
        $workshopsMissingDates = [];
        if ($user->isAdmin()) {
            foreach (Workshop::query()->where('status', 'active')->get() as $workshop) {
                $missing = ShiftService::getMissingScheduleDates(7, $workshop->id);
                if (! empty($missing)) {
                    $workshopsMissingDates[] = [
                        'workshop' => $workshop,
                        'dates' => $missing,
                    ];
                }
            }
        }

        return view('home', [
            'title' => 'Дашборд',
            'events' => ScheduleService::getScheduleByUserId($employeeId),
            'cutMarketplaceOrderItem' => MarketplaceOrderItemService::cut($workshopScope),
            'newMarketplaceOrderItem' => MarketplaceOrderItemService::new(),
            'marketplaceOrderItemInWork' => MarketplaceOrderItemService::toWork($workshopScope),
            'marketplaceOrderItemInCutting' => MarketplaceOrderItemService::toCutting($workshopScope),
            'urgentMarketplaceOrderItem' => MarketplaceOrderItemService::urgent($workshopScope),
            'notShippedMovements' => MovementMaterialToWorkshopService::getCountNotShippedMovements($user),
            'notReceivedMovements' => MovementMaterialToWorkshopService::getCountNotReceivedMovements($user),
            'stickeredMarketplaceOrderItem' => MovementMaterialToWorkshopService::getStickeredMarketplaceOrderItem($workshopScope),
            'employees' => User::query()
                ->where('name', 'not like', '%Тест%')
                ->paginate(5, ['*'], 'employees')->withQueryString(),
            'employeesForCalendar' => User::query()
                ->where('name', 'not like', '%Тест%')
                ->orderBy('name')
                ->get(),
            'currentUserId' => $user->id,
            'dates' => json_encode($dates),
            'seamstresses' => json_encode(MarketplaceOrderItemService::getSeamstressesLargeSizeRating($dates)),
            'days_ago' => $daysAgo,
            'rating' => MarketplaceOrderItemService::getRating(),
            'seamstressesCurrentSalary' => TransactionService::getSeamstressBalance('salary'),
            'seamstressesCurrentBonus' => TransactionService::getSeamstressBalance('bonus'),
            'seamstressesCurrentHoldBonus' => TransactionService::getSeamstressBalance('bonus', true),
            'pickupOrders' => MarketplaceOrderService::pickupOrders()->count(),
            'lowMaterialRollsCount' => RollService::getLowMaterialRollsCount(),
            'transactions' => Transaction::query()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->whereNotNull('user_id')
                ->where('transaction_type', 'in')
                ->whereIn('status', [1])
                ->paginate(5, ['*'], 'transactions')->withQueryString(),
            'gazelkaShipments' => $gazelkaShipments,
            'missingScheduleDates' => $missingScheduleDates,
            'workshopsMissingDates' => $workshopsMissingDates,
        ]);
    }
}
