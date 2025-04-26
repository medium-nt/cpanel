<?php

namespace App\Http\Controllers;

use App\Services\MarketplaceOrderItemService;
use App\Services\MovementMaterialToWorkshopService;
use App\Services\ScheduleService;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('home', [
            'title' => 'Дашборд',
            'events' => ScheduleService::getScheduleByUserId(auth()->id()),
            'newMarketplaceOrderItem' => MarketplaceOrderItemService::new(),
            'marketplaceOrderItemInWork' => MarketplaceOrderItemService::toWork(),
            'urgentMarketplaceOrderItem' => MarketplaceOrderItemService::urgent(),
            'notShippedMovements' => MovementMaterialToWorkshopService::getCountNotShippedMovements(),
            'notReceivedMovements' => MovementMaterialToWorkshopService::getCountNotReceivedMovements(),
        ]);
    }
}
