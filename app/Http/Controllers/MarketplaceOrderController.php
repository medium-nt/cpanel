<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMarketplaceOrderRequest;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Services\MarketplaceOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarketplaceOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = MarketplaceOrder::query()
            ->orderBy('marketplace_orders.created_at');

        $orders = match ($request->status){
            "3" => $orders->where('marketplace_orders.status', 3),
            "6" => $orders->where('marketplace_orders.status', 6),
            default => $orders->where('marketplace_orders.status', 0)
        };

        if (isset($request->marketplace_id)) {
            $orders = $orders->where('marketplace_orders.marketplace_id', $request->marketplace_id);
        }

        $queryParams = $request->except(['page']);

        return view('marketplace_orders.index', [
            'title' => 'Заказы',
            'orders' => $orders->paginate(10)->appends($queryParams),
        ]);
    }

    public function create()
    {
        return view('marketplace_orders.create', [
            'title' => 'Добавить заказ',
            'items' => MarketplaceItem::query()->get()
        ]);
    }

    public function store(StoreMarketplaceOrderRequest $request)
    {
        if(!MarketplaceOrderService::store($request)) {
            return back()->with(['error' => 'Внутренняя ошибка']);
        }

        return redirect()
            ->route('marketplace_orders.index')
            ->with('success', 'Заказ сформирован.');
    }

    public function edit(MarketplaceOrder $marketplaceOrder)
    {
        return view('marketplace_orders.edit', [
            'title' => 'Изменить заказ',
            'items' => MarketplaceItem::query()->get(),
            'order' => $marketplaceOrder
        ]);
    }

    public function update(Request $request, MarketplaceOrder $marketplaceOrder)
    {
        $data = [];
        foreach ($request->item_id ?? [] as $key => $item_id) {
            if ($request->quantity[$key] > 0) {
                $data[] = [
                    'order_id' => $request->order_id,
                    'marketplace_id' => $request->marketplace_id,
                    'item_id' => $item_id,
                    'quantity' => $request->quantity[$key],
                    'order_item_id' => $request->order_item_id[$key],
                    'fulfillment_type' => $request->fulfillment_type,
                ];
            }
        }

        $rules = [
            '*.order_item_id' => 'required|exists:marketplace_order_items,id',
            '*.order_id' => 'required',
            '*.marketplace_id' => 'required',
            '*.item_id' => 'required|exists:marketplace_items,id',
            '*.quantity' => 'required|integer|min:1',
            '*.fulfillment_type' => 'required|in:FBO,FBS',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated();

        $marketplaceOrder->update([
            'order_id' => $request->order_id,
            'marketplace_id' => $request->marketplace_id,
            'status' => 0,
            'fulfillment_type' => $request->fulfillment_type,
        ]);

        foreach ($validatedData as $item) {
            MarketplaceOrderItem::query()
                ->where('id', $item['order_item_id'])
                ->update([
                    'marketplace_item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                ]);
        }

        return redirect()
            ->route('marketplace_orders.index')
            ->with('success', 'Заказ изменен.');
    }

    public function complete(MarketplaceOrder $marketplaceOrder)
    {
        $marketplaceOrder->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Заказ выполнен.');
    }

    public function destroy(MarketplaceOrder $marketplaceOrder)
    {
        if ($marketplaceOrder->items->some(function ($item) {
            return $item->status != 0;
        })){
            return redirect()
                ->route('marketplace_orders.index')
                ->with('error', 'Товары заказа уже переданы в работу. Заказ не может быть удален.');
        }

        $marketplaceOrder->delete();

        return redirect()
            ->route('marketplace_orders.index')
            ->with('success', 'Заказ удален.');
    }

    public function remove(MarketplaceOrder $marketplace_order)
    {
        $marketplace_supply = $marketplace_order->supply->id;

        $marketplace_order->supply_id = null;
        $marketplace_order->save();

        return redirect()
            ->route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply])
            ->with('success', 'Заказ удален из поставки.');

    }
}
