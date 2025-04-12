<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrderItem;
use App\Models\Order;
use App\Services\MarketplaceOrderItemService;
use Illuminate\Http\Request;

class MarketplaceOrderItemController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status ?? 'in_work';
        $items = MarketplaceOrderItemService::getMarketplaceOrdersByStatus($status);

        return view('marketplace_order_items.index', [
            'title' => 'Товары для пошива',
            'items' => $items->paginate(10)
        ]);
    }

    public function startWork(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        $result = MarketplaceOrderItemService::acceptToSeamstress($marketplaceOrderItem);

        if (!$result['success']) {
            return redirect()->route('marketplace_order_items.index', ['status' => 'new'])->with('error', $result['message']);
        }

        return redirect()->route('marketplace_order_items.index')->with('success', 'Заказ принят');
    }

    public function done(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        Order::query()
            ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id)
            ->update([
                'status' => 3,
                'completed_at' => now()
        ]);

        $marketplaceOrderItem->update([
            'status' => 3,
        ]);

        return redirect()->route('marketplace_order_items.index')->with('success', 'Заказ сдан');
    }

}
