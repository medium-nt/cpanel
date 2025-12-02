<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Services\MarketplaceOrderItemService;
use App\Services\MarketplaceOrderService;
use App\Services\MovementMaterialToWorkshopService;
use App\Services\ScheduleService;
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

        $dates = MarketplaceOrderItemService::getDatesByLargeSizeRating($daysAgo);

        return view('home', [
            'title' => 'Дашборд',
            'events' => ScheduleService::getScheduleByUserId($employeeId),
            'cutMarketplaceOrderItem' => MarketplaceOrderItemService::cut(),
            'newMarketplaceOrderItem' => MarketplaceOrderItemService::new(),
            'marketplaceOrderItemInWork' => MarketplaceOrderItemService::toWork(),
            'marketplaceOrderItemInCutting' => MarketplaceOrderItemService::toCutting(),
            'urgentMarketplaceOrderItem' => MarketplaceOrderItemService::urgent(),
            'notShippedMovements' => MovementMaterialToWorkshopService::getCountNotShippedMovements(),
            'notReceivedMovements' => MovementMaterialToWorkshopService::getCountNotReceivedMovements(),
            'employees' => User::query()
                ->where('name', 'not like', '%Тест%')
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
            'transactions' => Transaction::query()
                ->orderBy('created_at', 'desc')
                ->whereNotNull('user_id')
                ->where('transaction_type', 'in')
                ->whereIn('status', [1])
                ->paginate(5)->withQueryString(),
        ]);
    }
}
