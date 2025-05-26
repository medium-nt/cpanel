<?php

namespace App\Http\Controllers;

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

        return view('sticker_printing', [
            'title' => 'Печать стикеров',
            'seamstressId' => $request->seamstress_id ?? 0,
            'items' => MarketplaceOrderItemService::getItemsForLabeling($request),
            'seamstresses' => User::query()->where('role_id', '1')
                ->where('name', 'not like', '%Тест%')->get(),
            'dates' => json_encode($dates),
            'seamstressesJson' => json_encode(MarketplaceOrderItemService::getSeamstressesLargeSizeRating($dates)),
            'days_ago' => $daysAgo
        ]);
    }
}
