<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceSupply;
use App\Models\SupplyBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupplyBoxController extends Controller
{
    /**
     * Список коробов поставки.
     */
    public function index(MarketplaceSupply $marketplaceSupply)
    {
        $boxes = SupplyBox::query()
            ->withCount('orders')
            ->where('marketplace_supply_id', $marketplaceSupply->id)
            ->get();

        $freeOrdersCount = MarketplaceOrder::query()
            ->where('supply_id', $marketplaceSupply->id)
            ->whereNull('box_id')
            ->count();

        return view('supply_box.index', [
            'title' => 'Коробы поставки #'.$marketplaceSupply->supply_id,
            'supply' => $marketplaceSupply,
            'boxes' => $boxes,
            'freeOrdersCount' => $freeOrdersCount,
        ]);
    }

    /**
     * Создание нового короба.
     */
    public function store(Request $request, MarketplaceSupply $marketplaceSupply)
    {
        $box = SupplyBox::query()->create([
            'marketplace_supply_id' => $marketplaceSupply->id,
            'number' => '',
        ]);

        Log::channel('marketplace_supplies')
            ->notice(auth()->user()->name.' создал короб #'.$box->number.' для поставки #'.$marketplaceSupply->id.'.');

        return redirect()
            ->route('supply_boxes.show', ['marketplace_supply' => $marketplaceSupply, 'box' => $box])
            ->with('success', 'Короб создан.');
    }

    /**
     * Удаление пустого короба.
     */
    public function destroy(MarketplaceSupply $marketplaceSupply, SupplyBox $box)
    {
        if ($box->orders()->exists()) {
            return back()->with('error', 'Нельзя удалить короб с заказами.');
        }

        $box->delete();

        return redirect()
            ->route('supply_boxes.index', ['marketplace_supply' => $marketplaceSupply])
            ->with('success', 'Короб удалён.');
    }

    /**
     * Страница короба — заказы внутри.
     */
    public function show(MarketplaceSupply $marketplaceSupply, SupplyBox $box)
    {
        $box->load('orders.items.item');

        return view('supply_box.show', [
            'title' => 'Короб '.$box->number,
            'supply' => $marketplaceSupply,
            'box' => $box,
        ]);
    }

    /**
     * Убрать заказ из короба.
     */
    public function removeOrder(MarketplaceSupply $marketplaceSupply, SupplyBox $box, MarketplaceOrder $order)
    {
        if ($order->box_id === $box->id) {
            $order->update(['box_id' => null]);
        }

        return back()->with('success', 'Заказ убран из короба.');
    }

    /**
     * Закрыть короб — запретить добавление и удаление товаров.
     */
    public function closeBox(MarketplaceSupply $marketplaceSupply, SupplyBox $box)
    {
        if ($box->closed_at) {
            return back()->with('error', 'Короб уже закрыт.');
        }

        if ($box->orders()->count() === 0) {
            return back()->with('error', 'Нельзя закрыть пустой короб.');
        }

        $box->update(['closed_at' => now()]);

        return redirect()
            ->route('supply_boxes.show', ['marketplace_supply' => $marketplaceSupply, 'box' => $box])
            ->with('success', 'Короб закрыт.');
    }
}
