<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceSupply;
use Illuminate\Http\Request;

class MarketplaceSupplyController extends Controller
{
    public function index(Request $request)
    {
        $supplies = MarketplaceSupply::query()
            ->orderBy('created_at');

        if($request->status != 0) {
            $supplies = $supplies->where('status', '!=', 0);
        } else {
            $supplies = $supplies->where('status', 0);
        }

        if (isset($request->marketplace_id)) {
            $supplies = $supplies->where('marketplace_id', $request->marketplace_id);
        }

        $queryParams = $request->except(['page']);

        return view('marketplace_supply.index', [
            'title' => 'Поставки маркетплейса',
            'marketplace_supplies' => $supplies->paginate(10)->appends($queryParams)
        ]);
    }

    public function show(MarketplaceSupply $marketplaceSupply)
    {
        $marketplaceName = match ($marketplaceSupply->marketplace_id) {
            1 => 'OZON',
            2 => 'WB',
        };

        $totalReady = MarketplaceOrder::query()
            ->where('status', 6)
            ->where('marketplace_id', $marketplaceSupply->marketplace_id)
            ->count();

        return view('marketplace_supply.show', [
            'title' => 'Поставка для маркетплейса ' . $marketplaceName,
            'supply' => $marketplaceSupply,
            'supply_orders' => $marketplaceSupply->marketplace_orders()->get(),
            'totalReady' => $totalReady,
        ]);
    }

    public function create(string $marketplace_id)
    {
        MarketplaceSupply::query()->create([
            'marketplace_id' => $marketplace_id,
        ]);

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка создана.');
    }

    public function destroy(MarketplaceSupply $marketplace_supply)
    {
        if($marketplace_supply->marketplace_orders->count() > 0) {
            return redirect()
                ->route('marketplace_supplies.index')
                ->with('error', 'Нельзя удалить поставку, которая содержит заказы.');
        }

        MarketplaceSupply::query()->findOrFail($marketplace_supply->id)->delete();

        return redirect()
            ->route('marketplace_supplies.index')
            ->with('success', 'Поставка удалена.');
    }

}
