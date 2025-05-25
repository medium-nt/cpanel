<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrderItem;
use App\Models\User;
use App\Services\MarketplaceOrderItemService;
use Illuminate\Http\Request;

class StickerPrintingController extends Controller
{
    public function index(Request $request)
    {
        $daysAgo = $request->input('days_ago') ?? 0;
        $daysAgo = intval($daysAgo);

        if ($daysAgo < 0 || $daysAgo > 28) {
            $daysAgo = 0;
        }

        $dates = MarketplaceOrderItemService::getDatesByLargeSizeRating($daysAgo);

        $items = MarketplaceOrderItem::query()
            ->where('marketplace_order_items.status', '5')
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->select('marketplace_order_items.*');

        if ($request->has('seamstress_id')) {
            $items = $items->where('marketplace_order_items.seamstress_id', $request->seamstress_id);
        } else {
            $items = $items->where('marketplace_order_items.seamstress_id', 0);
        }

        return view('sticker_printing', [
            'title' => 'Печать стикеров',
            'items' => $items->get(),
            'seamstresses' => User::query()->where('role_id', '1')
                ->where('name', 'not like', '%Тест%')->get(),
            'dates' => json_encode($dates),
            'seamstressesJson' => json_encode(MarketplaceOrderItemService::getSeamstressesLargeSizeRating($dates)),
            'days_ago' => $daysAgo
        ]);
    }
}
