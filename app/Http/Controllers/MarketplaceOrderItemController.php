<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Order;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\MarketplaceApiService;
use App\Services\MarketplaceOrderItemService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketplaceOrderItemController extends Controller
{
    public function index(Request $request)
    {
        //  запретить швеям смотреть новые заказы и заказы в закрое.
        if(($request->status == 'new' || $request->status == 'cutting') && auth()->user()->isSeamstress()) {
            return redirect()->route('marketplace_order_items.index', ['status' => 'in_work']);
        }
        // запретить закройщикам смотреть новые заказы
        if($request->status == 'new' && auth()->user()->isCutter()) {
            return redirect()->route('marketplace_order_items.index', ['status' => 'cutting']);
        }

        $items = MarketplaceOrderItemService::getFiltered($request);
        $paginatedItems = $items->paginate(50);

        $queryParams = $request->except(['page']);

        return view('marketplace_order_items.index', [
            'title' => 'Товары для пошива',
            'items' => $paginatedItems->appends($queryParams),
//            'materials' => InventoryService::materialsQuantityBy('workhouse'),
            'bonus' => TransactionService::getBonusForTodayOrdersByUsers(),
            'users' => User::query()->whereIn('role_id', [1, 4])
                ->where('name', 'not like', '%Тест%')->get()
        ]);
    }

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

        $marketplaceOrderItem->update([
            'status' => 3,
            'completed_at' => now()
        ]);

        $text = 'Швея ' . $marketplaceOrderItem->seamstress->name .
            ' (' . $marketplaceOrderItem->seamstress->id . ') выполнила заказ ' . $marketplaceOrderItem->marketplaceOrder->order_id .
            ' (товар #' . $marketplaceOrderItem->id . ')';
        Log::channel('erp')->notice($text);

        return back()->with('success', 'Заказ успешно выполнен');
    }

    public function cancel(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        $result = MarketplaceOrderItemService::cancelToSeamstress($marketplaceOrderItem);

        if (!$result['success']) {
            return redirect()
                ->back()
                ->with('error', $result['message']);
        }

        return redirect()
            ->back()
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

        $text = 'Швея ' . $marketplaceOrderItem->seamstress->name .
            ' (' . $marketplaceOrderItem->seamstress->id . ') передала товар #' . $marketplaceOrderItem->id .
            ' (заказ ' . $marketplaceOrderItem->marketplaceOrder->order_id . ') на стикеровку';

        Log::channel('erp')->info($text);

        $marketplaceOrderItem->update([
            'status' => 5,
            'completed_at' => now()
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

    public function completeCutting(Request $request, MarketplaceOrderItem $marketplaceOrderItem)
    {
        Order::query()
            ->where('marketplace_order_id', $marketplaceOrderItem->marketplaceOrder->id)
            ->update([
                'status' => 3,
                'completed_at' => now()
            ]);

        $marketplaceOrderItem->update([
            'status' => 8,
            'cutting_completed_at' => now()
        ]);

        $text = 'Закройщик ' . $marketplaceOrderItem->cutter->name .
            ' (' . $marketplaceOrderItem->cutter->id . ') выполнил заказ ' . $marketplaceOrderItem->marketplaceOrder->order_id .
            ' (товар #' . $marketplaceOrderItem->id . ')';
        Log::channel('erp')->notice($text);

        return back()->with('success', 'Заказ успешно выполнен');
    }
}
