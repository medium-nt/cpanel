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
        $employeeId = $request->input('employee_id') ?? auth()->id();

        $daysAgo = $request->input('days_ago') ?? 0;
        $daysAgo = intval($daysAgo);

        if ($daysAgo < 0 || $daysAgo > 28) {
            $daysAgo = 0;
        }

        $gazelkaShipments = MarketplaceSupply::query()
            ->whereNotNull('gazelka_shipment_date')
            ->where('gazelka_shipment_date', '>=', now()->startOfDay())
            ->where('gazelka_shipment_date', '<=', now()->addDays(3)->endOfDay())
            ->orderBy('gazelka_shipment_date')
            ->get();

        $dates = MarketplaceOrderItemService::getDatesByLargeSizeRating($daysAgo);

        $missingScheduleDates = auth()->user()->isAdmin()
            ? ShiftService::getMissingScheduleDates()
            : [];

        // Непроставленные даты по цехам
        $workshopsMissingDates = [];
        if (auth()->user()->isAdmin()) {
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
            'cutMarketplaceOrderItem' => MarketplaceOrderItemService::cut(),
            'newMarketplaceOrderItem' => MarketplaceOrderItemService::new(),
            'marketplaceOrderItemInWork' => MarketplaceOrderItemService::toWork(),
            'marketplaceOrderItemInCutting' => MarketplaceOrderItemService::toCutting(),
            'urgentMarketplaceOrderItem' => MarketplaceOrderItemService::urgent(),
            'notShippedMovements' => MovementMaterialToWorkshopService::getCountNotShippedMovements(auth()->user()),
            'notReceivedMovements' => MovementMaterialToWorkshopService::getCountNotReceivedMovements(auth()->user()),
            'stickeredMarketplaceOrderItem' => MovementMaterialToWorkshopService::getStickeredMarketplaceOrderItem(),
            'employees' => User::query()
                ->where('name', 'not like', '%Тест%')
                ->paginate(5, ['*'], 'employees')->withQueryString(),
            'employeesForCalendar' => User::query()
                ->where('name', 'not like', '%Тест%')
                ->orderBy('name')
                ->get(),
            'currentUserId' => auth()->id(),
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
