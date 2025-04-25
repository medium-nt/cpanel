<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Order;
use App\Models\Schedule;
use App\Services\MarketplaceOrderItemService;
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

            'newMarketplaceOrderItem' => MarketplaceOrderItem::query()
                ->where('status', 0)
                ->count(),

            'marketplaceOrderItemInWork' => MarketplaceOrderItem::query()
                ->where('status', 4)
                ->count(),

            'urgentMarketplaceOrderItem' => MarketplaceOrderItem::query()
                ->join('marketplace_orders', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
                ->whereIn('marketplace_order_items.status', [0, 4])
                ->where('marketplace_orders.fulfillment_type', 'FBS')
                ->count(),

            'notShippedMovements' => Order::query()
                ->where('type_movement', 2)
                ->where('status', 0)
                ->count(),

            'notReceivedMovements' => Order::query()
                ->where('type_movement', 2)
                ->where('status', 2)
                ->count(),
        ]);
    }
}
