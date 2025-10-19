<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Shelf;
use App\Models\Sku;
use App\Services\MarketplaceApiService;
use App\Services\MarketplaceItemService;
use App\Services\MarketplaceOrderItemService;
use App\Services\MarketplaceOrderService;
use App\Services\WarehouseOfItemService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseOfItemController extends Controller
{
    public function index(Request $request, WarehouseOfItemService $warehouseOfItemService)
    {
        $filteredItems = $warehouseOfItemService->getFiltered($request);

        return view('warehouse_of_item.index', [
            'title' => 'Склад товара',
            'materials' => MarketplaceItemService::getAllTitleMaterials(),
            'widths' => MarketplaceItemService::getAllWidthMaterials(),
            'heights' => MarketplaceItemService::getAllHeightMaterials(),
            'shelves' => Shelf::all(),
            'totalItems' => $filteredItems->count(),
            'items' => $filteredItems->paginate(20),
        ]);
    }

    public function newRefunds(Request $request, WarehouseOfItemService $warehouseOfItemService)
    {
        $result = $warehouseOfItemService->findRefundItemByBarcode($request->barcode);

        return view('warehouse_of_item.new_refunds', [
            'title' => 'Товар с возврата',
            'marketplace_item' => $result['marketplace_item'],
            'marketplace_items' => $result['marketplace_items'],
            'barcode' => $request->barcode ?? '',
            'message' => $result['message'],
            'shelves' => Shelf::all(),
            'returnReason' => $result['returnReason'],
        ]);
    }

    public function getStorageBarcodeFile(MarketplaceOrderItem $marketplace_item, WarehouseOfItemService $service, $copiesCount = 2)
    {
        $pdf = PDF::loadView('pdf.storage_barcode_sticker', [
            'barcode' => $service->getStorageBarcode($marketplace_item),
            'items' => collect()->times($copiesCount, fn() => $marketplace_item),
            'seamstressName' => $marketplace_item->marketplaceOrder?->items?->first()?->seamstress?->name ?? '---',
        ]);

        $pdf->setPaper('A4');
        return $pdf->stream('barcode.pdf');
    }

    public function saveStorage(Request $request, MarketplaceOrderItem $marketplace_item, WarehouseOfItemService $service)
    {
        if (!$marketplace_item->storage_barcode) {
            return redirect()
                ->route('warehouse_of_item.new_refunds',
                    ['barcode' => $marketplace_item->marketplaceOrder->order_id])
                ->with('error', 'Не распечатан штрихкод хранения!');
        }

        if (!$request->shelf_id) {
            return redirect()
                ->route('warehouse_of_item.new_refunds',
                    ['barcode' => $marketplace_item->marketplaceOrder->order_id])
                ->with('error', 'Не указан номер полки!');
        }

        $service->saveItemToStorage($marketplace_item, $request->shelf_id);

        return redirect()
            ->route('warehouse_of_item.index')
            ->with('success', 'Изменения сохранены');
    }

    public function toPickList()
    {
        return view('warehouse_of_item.to_pick_list', [
            'title' => 'Товары для подбора со склада',
            'orders' => MarketplaceOrderService::pickupOrders()
                ->paginate(20),
            'ordersAssembled' => MarketplaceOrderService::assembledOrders(),
        ]);
    }

    public function toPick(MarketplaceOrder $order, Request $request)
    {
        $itemModel = $order->items[0]->item;
        $itemName = "{$itemModel->title} {$itemModel->width}x{$itemModel->height}";

        $items = MarketplaceOrderItem::query()
            ->where('marketplace_item_id', $itemModel->id)
            ->whereIn('status', [11, 13]);

        $shelfStats = (clone $items)
            ->select('shelf_id', DB::raw('COUNT(*) as quantity'))
            ->groupBy('shelf_id')
            ->get();

        $item = $request->barcode
            ? (clone $items)->where('storage_barcode', $request->barcode)->first()
            : null;

        return view('warehouse_of_item.to_pick', [
            'title' => "Сборка товара $itemName",
            'itemName' => $itemName,
            'barcode' => $request->barcode,
            'order' => $order,
            'item' => $item,
            'itemsCount' => $items->count(),
            'shelfStats' => $shelfStats,
        ]);
    }

    public function labeling(Request $request, MarketplaceOrder $marketplaceOrder, MarketplaceOrderItem $marketplaceOrderItem)
    {
        $orderId = $marketplaceOrder->order_id;
        $sku = $marketplaceOrderItem->item->sku()->first()->sku;

        $result = match ($marketplaceOrder->marketplace_id) {
            1 => MarketplaceApiService::collectOrderOzon($orderId, $sku),
            2 => MarketplaceApiService::collectOrderWb($orderId),
            default => false,
        };

        if (!$result) {
            Log::channel('marketplace_api')
                ->error('Не удалось передать заказ ' . $orderId . ' c sku: ' . $sku . ' на стикеровку');
            return redirect()->back()
                ->with('error', 'Не удалось передать заказ на стикеровку');
        }

        $text = 'Кладовщик ' . auth()->user()->name .
            ' передал товар #' . $marketplaceOrderItem->id .
            ' (заказ ' . $marketplaceOrder->order_id . ') на стикеровку';

        Log::channel('erp')->info($text);

        $selectedMarketplaceOrderItem = $marketplaceOrder->items->first();

        if ($marketplaceOrderItem->id !== $selectedMarketplaceOrderItem->id) {
            Log::channel('erp')
                ->info('Для заказа ' . $orderId . ' передан товар ' . $marketplaceOrderItem->id .
                    ' вместо ранее выбранного ' . $selectedMarketplaceOrderItem->id);

            if ($marketplaceOrderItem->marketplaceOrder->status === '13' || $marketplaceOrderItem->marketplaceOrder->status === '5') {
                $tempOrderId = $marketplaceOrderItem->marketplace_order_id;
                $marketplaceOrderItem->marketplace_order_id = $selectedMarketplaceOrderItem->marketplace_order_id;
                $selectedMarketplaceOrderItem->marketplace_order_id = $tempOrderId;

                $marketplaceOrderItem->save();
                $selectedMarketplaceOrderItem->save();

                Log::channel('erp')
                    ->info('Есть еще заказ с таким же товаром на сборке или стикеровке! ' .
                        ' Поменяли местами товары в этих заказах. ' .
                        ' В заказ: ' . $marketplaceOrderItem->marketplace_order_id .
                        ' передали товар ' . $selectedMarketplaceOrderItem->id .
                        ', а в заказ ' . $selectedMarketplaceOrderItem->marketplace_order_id .
                        ' передали товар ' . $marketplaceOrderItem->id);
            } else {
                MarketplaceOrderItemService::restoreOrderFromHistory($selectedMarketplaceOrderItem);
                MarketplaceOrderItemService::saveOrderToHistory($marketplaceOrderItem);
            }
        }

        $marketplaceOrderItem->marketplace_order_id = $marketplaceOrder->id;
        $marketplaceOrderItem->status = 13; // в сборке
        $marketplaceOrderItem->save();

        $marketplaceOrder->status = 5; // в стикеровке
        $marketplaceOrder->save();

        return redirect()->back()
            ->with('success', 'Заказ передан на стикеровку');
    }

    public function done(MarketplaceOrder $marketplaceOrder)
    {
        if (!$marketplaceOrder->is_printed) {
            return redirect()->back()
                ->with('error', 'Стикер не распечатан!');
        }

        $marketplaceOrder->status = 6; // на поставку
        $marketplaceOrder->completed_at = now();
        $marketplaceOrder->save();

        $marketplaceOrderItem = $marketplaceOrder->items->first();
        $marketplaceOrderItem->status = 3; // выполнен
        $marketplaceOrderItem->save();

        return redirect()->route('warehouse_of_item.index')
            ->with('success', 'Заказ передан на поставку');
    }

    public function toWork(MarketplaceOrder $marketplaceOrder)
    {
        if ($marketplaceOrder->status != 13) {
            return redirect()->back()
                ->with('error', 'Заказ не находится в сборке!');
        }

        $marketplaceOrderItem = $marketplaceOrder->items->first();
        MarketplaceOrderItemService::restoreOrderFromHistory($marketplaceOrderItem);

        $marketplaceOrder->status = 0;
        $marketplaceOrder->save();

        $sku = Sku::query()
            ->where('item_id', $marketplaceOrderItem->marketplace_item_id)
            ->where('marketplace_id', $marketplaceOrder->marketplace_id)
            ->first();

        MarketplaceOrderItemService::createItem($sku, $marketplaceOrder);

        return redirect()->route('warehouse_of_item.to_pick_list')
            ->with('success', 'Заказ передан в цех на пошив');
    }
}
