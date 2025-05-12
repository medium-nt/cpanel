<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MarketplaceOrderItemService;
use App\Services\MovementMaterialToWorkshopService;
use App\Services\ScheduleService;
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

        return view('home', [
            'title' => 'Дашборд',
            'events' => ScheduleService::getScheduleByUserId($employeeId),
            'newMarketplaceOrderItem' => MarketplaceOrderItemService::new(),
            'marketplaceOrderItemInWork' => MarketplaceOrderItemService::toWork(),
            'urgentMarketplaceOrderItem' => MarketplaceOrderItemService::urgent(),
            'notShippedMovements' => MovementMaterialToWorkshopService::getCountNotShippedMovements(),
            'notReceivedMovements' => MovementMaterialToWorkshopService::getCountNotReceivedMovements(),
            'employees' => User::query()->get(),
            'currentUserId' => auth()->id()
        ]);
    }
}
