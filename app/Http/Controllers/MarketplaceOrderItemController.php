<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrderItem;
use Illuminate\Http\Request;

class MarketplaceOrderItemController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status ?? 'in_work';

        $items = MarketplaceOrderItem::query()
            ->when($status === 'new', function ($query) {
                return $query->where('status', '0');
            })
            ->when($status === 'in_work' || $status === 'done', function ($query) use ($status) {
                return $query->where('status', $status === 'in_work' ? 4 : 3)
                    ->where('seamstress_id', auth()->user()->id);
            })
            ->paginate(10);

        return view('marketplace_order_items.index', [
            'title' => 'Товары для пошива',
            'items' => $items
        ]);
    }

    public function startWork(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        $marketplaceOrderItem->update([
            'status' => 4,
            'seamstress_id' => auth()->user()->id
        ]);

        return redirect()->route('marketplace_order_items.index')->with('success', 'Заказ принят');
    }

    public function done(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        $marketplaceOrderItem->update([
            'status' => 3,
        ]);

        return redirect()->route('marketplace_order_items.index')->with('success', 'Заказ сдан');
    }

}
