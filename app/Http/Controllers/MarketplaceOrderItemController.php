<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrderItem;
use App\Services\MovementMaterialService;
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
        $quantityOrderItem = $marketplaceOrderItem->quantity;

        $marketplaceItem = $marketplaceOrderItem->item()->first();
        $materialConsumptions = $marketplaceItem->consumption;

        if ($materialConsumptions->isEmpty()) {
            return redirect()->route('marketplace_order_items.index', ['status' => 'new'])
                ->with('error', 'Для этого заказа не указаны материалы!');
        }

        foreach ($materialConsumptions as $materialConsumption) {
            $materialId = $materialConsumption->material_id;
            $materialConsumptionQuantity = $materialConsumption->quantity;

            $inWorkshop = MovementMaterialService::countMaterial($materialId, 2, 3);
            $outWorkshop = MovementMaterialService::countMaterial($materialId, 3, 3);
            $holdWorkshop = 0;

            $materialInWorkhouse = $inWorkshop - $outWorkshop - $holdWorkshop;

            if ($materialInWorkhouse < $materialConsumptionQuantity * $quantityOrderItem) {
                return redirect()->route('marketplace_order_items.index', ['status' => 'new'])
                    ->with('error', 'Для этого заказа на производстве недостаточно материала!');
            }
        }

        $marketplaceOrderItem->update([
            'status' => 4,
            'seamstress_id' => auth()->user()->id
        ]);

        return redirect()->route('marketplace_order_items.index')->with('success', 'Заказ принят');
    }

    public function done(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
//        TO_DO списать материал

        $marketplaceOrderItem->update([
            'status' => 3,
        ]);

        return redirect()->route('marketplace_order_items.index')->with('success', 'Заказ сдан');
    }

}
