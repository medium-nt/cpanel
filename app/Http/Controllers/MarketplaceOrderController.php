<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarketplaceOrderController extends Controller
{
    public function index()
    {
        return view('marketplace_orders.index', [
            'title' => 'Заказы',
            'orders' => MarketplaceOrder::query()->paginate(10)
        ]);
    }

    public function create()
    {
        return view('marketplace_orders.create', [
            'title' => 'Добавить материал',
            'items' => MarketplaceItem::query()->get()
        ]);
    }

    public function store(Request $request)
    {
        if (!is_array($request->item_id)) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Заказ не может быть пустым'])
                ->withInput();
        }

        $data = [];
        foreach ($request->item_id as $key => $item_id) {
            if ($request->quantity[$key] > 0 && $request->price[$key] > 0) {
                $data[] = [
                    'order_id' => $request->order_id,
                    'marketplace_id' => $request->marketplace_id,
                    'item_id' => $item_id,
                    'quantity' => $request->quantity[$key],
                    'price' => $request->price[$key]
                ];
            }
        }

        if ($data == []) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Заказ не может быть пустым'])
                ->withInput();
        }

        $rules = [
            '*.order_id' => 'required',
            '*.marketplace_id' => 'required',
            '*.item_id' => 'required|exists:marketplace_items,id',
            '*.quantity' => 'required|integer|min:1',
            '*.price' => 'required|integer|min:1'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated();

        $marketplaceOrder = MarketplaceOrder::query()->create([
            'order_id' => $request->order_id,
            'marketplace_id' => $request->marketplace_id,
            'status' => 0
        ]);

        foreach ($validatedData as $item) {
            $movementData['marketplace_order_id'] = $marketplaceOrder->id;
            $movementData['marketplace_item_id'] = $item['item_id'];
            $movementData['quantity'] = $item['quantity'];
            $movementData['price'] = $item['price'];

            MarketplaceOrderItem::query()->create($movementData);
        }

        return redirect()
            ->route('marketplace_orders.index')
            ->with('success', 'Заказ сформирован.');
    }

    public function show(MarketplaceOrder $marketplaceOrder)
    {
        //
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
        if (!is_array($request->item_id)) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Заказ не может быть пустым'])
                ->withInput();
        }

        $data = [];
        foreach ($request->item_id as $key => $item_id) {
            if ($request->quantity[$key] > 0 && $request->price[$key] > 0) {
                $data[] = [
                    'order_id' => $request->order_id,
                    'marketplace_id' => $request->marketplace_id,
                    'item_id' => $item_id,
                    'quantity' => $request->quantity[$key],
                    'price' => $request->price[$key],
                    'order_item_id' => $request->order_item_id[$key]
                ];
            }
        }

        if ($data == []) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Заказ не может быть пустым'])
                ->withInput();
        }

        $rules = [
            '*.order_item_id' => 'required|exists:marketplace_order_items,id',
            '*.order_id' => 'required',
            '*.marketplace_id' => 'required',
            '*.item_id' => 'required|exists:marketplace_items,id',
            '*.quantity' => 'required|integer|min:1',
            '*.price' => 'required|numeric|min:1'
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
            'status' => 0
        ]);

        foreach ($validatedData as $item) {
            MarketplaceOrderItem::query()
                ->where('id', $item['order_item_id'])
                ->update([
                    'marketplace_item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
        }

        return redirect()
            ->route('marketplace_orders.index')
            ->with('success', 'Заказ изменен.');
    }

    public function destroy(MarketplaceOrder $marketplaceOrder)
    {
        if($marketplaceOrder->status != 0) {
            return redirect()
                ->route('marketplace_orders.index')
                ->with('error', 'Заказ уже передан в работу и не может быть удален.');
        }

        $marketplaceOrder->delete();

        return redirect()
            ->route('marketplace_orders.index')
            ->with('success', 'Заказ удален.');
    }
}
