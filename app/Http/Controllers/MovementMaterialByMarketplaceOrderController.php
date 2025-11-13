<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class MovementMaterialByMarketplaceOrderController extends Controller
{
    public function index(Request $request)
    {
        return view('movements_by_marketplace_order.index', [
            'title' => 'Расход материала на заказы',
            'orders' => Order::query()
                ->where('type_movement', 3)
                ->latest()
                ->paginate(100),
        ]);
    }
}
