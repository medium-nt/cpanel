<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrderItem;
use App\Models\User;
use Illuminate\Http\Request;

class StickerPrintingController extends Controller
{
    public function index(Request $request)
    {
        $items = MarketplaceOrderItem::query()
            ->where('marketplace_order_items.status', '5')
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->select('marketplace_order_items.*');

        if ($request->has('seamstress_id')) {
            $items = $items->where('marketplace_order_items.seamstress_id', $request->seamstress_id);
        }

        return view('sticker_printing', [
            'title' => 'Печать стикеров',
            'items' => $items->get(),
            'seamstresses' => User::query()->where('role_id', '1')->get()
        ]);
    }
}
