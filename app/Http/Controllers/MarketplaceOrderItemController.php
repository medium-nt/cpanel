<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrderItem;
use App\Models\Order;
use App\Models\User;
use App\Services\MarketplaceOrderItemService;
use Illuminate\Http\Request;

class MarketplaceOrderItemController extends Controller
{
    public function index(Request $request)
    {
        $items = MarketplaceOrderItemService::getFiltered($request);
        $paginatedItems = $items->paginate(50);

        $queryParams = $request->except(['page']);

        return view('marketplace_order_items.index', [
            'title' => 'Товары для пошива',
            'items' => $paginatedItems->appends($queryParams),
            'seamstresses' => User::query()->where('role_id', '1')
                ->where('name', 'not like', '%Тест%')->get()
        ]);
    }

    public function startWork(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        $result = MarketplaceOrderItemService::acceptToSeamstress($marketplaceOrderItem);

        if (!$result['success']) {
            return redirect()
                ->route('marketplace_order_items.index', ['status' => 'new'])
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('marketplace_order_items.index')
            ->with('success', $result['message']);
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
            'completed_at' => now()
        ]);

        return back()->with('success', 'Заказ успешно выполнен');
    }

    public function cancel(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        $result = MarketplaceOrderItemService::cancelToSeamstress($marketplaceOrderItem);

        if (!$result['success']) {
            return redirect()
                ->route('marketplace_order_items.index', ['status' => 'new'])
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('marketplace_order_items.index')
            ->with('success', $result['message']);
    }

    public function labeling(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        $marketplaceOrderItem->update([
            'status' => 5
        ]);

        return redirect()->route('marketplace_order_items.index')
            ->with('success', 'Заказ передан на стикеровку');
    }

}
