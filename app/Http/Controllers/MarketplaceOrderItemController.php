<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Order;
use App\Models\User;
use App\Services\MarketplaceApiService;
use App\Services\MarketplaceOrderItemService;
use App\Services\StackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketplaceOrderItemController extends Controller
{
    public function index(Request $request)
    {
        //  запретить швеям смотреть новые заказы
        if($request->status == 'new' && auth()->user()->role->name === 'seamstress') {
            return redirect()->route('marketplace_order_items.index');
        }

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

//    public function startWork(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
//    {
//        $result = MarketplaceOrderItemService::acceptToSeamstress($marketplaceOrderItem);
//
//        if (!$result['success']) {
//            return redirect()
//                ->route('marketplace_order_items.index', ['status' => 'new'])
//                ->with('error', $result['message']);
//        }
//
//        return redirect()
//            ->route('marketplace_order_items.index')
//            ->with('success', $result['message']);
//    }

    public function done(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        Order::query()
            ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id)
            ->update([
                'status' => 3,
                'completed_at' => now()
        ]);

        MarketplaceOrder::query()
            ->where('id', $marketplaceOrderItem->marketplaceOrder->id)
            ->update([
                'status' => 6,
                'completed_at' => now()
            ]);

        //  добавляем -1 к стэку и проверяем что если это последний заказ в стэке, то обнуляем стэк.
//        StackService::reduceStack($marketplaceOrderItem->seamstress_id);

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
        $fulfillmentType = $marketplaceOrderItem->marketplaceOrder->fulfillment_type;

        if ($fulfillmentType === 'FBS') {
            $orderId = $marketplaceOrderItem->marketplaceOrder->order_id;
            $sku = $marketplaceOrderItem->item->sku()->first()->sku;

            $result = match ($marketplaceOrderItem->marketplaceOrder->marketplace_id) {
                1 => MarketplaceApiService::collectOrderOzon($orderId, $sku),
                2 => MarketplaceApiService::collectOrderWb($orderId),
                default => false,
            };

            if (!$result) {
                Log::channel('marketplace_api')
                    ->error('Не удалось передать заказ ' . $orderId . ' c sku: ' . $sku . ' на стикеровку');
                return redirect()->route('marketplace_order_items.index')
                    ->with('error', 'Не удалось передать заказ на стикеровку');
            }
        }

        $marketplaceOrderItem->update([
            'status' => 5
        ]);

        return redirect()->route('marketplace_order_items.index')
            ->with('success', 'Заказ передан на стикеровку');
    }

    public function getNewOrderItem()
    {
        $result = MarketplaceOrderItemService::getNewOrderItem();
        if ($result['success']) {
            return redirect()
                ->route('marketplace_order_items.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('marketplace_order_items.index')
            ->with('error', $result['message']);
    }

}
