<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrderItem;
use App\Models\Shelf;
use App\Services\MarketplaceItemService;
use App\Services\WarehouseOfItemService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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

    public function getStorageBarcodeFile(MarketplaceOrderItem $marketplace_item, WarehouseOfItemService $service)
    {
        $pdf = PDF::loadView('pdf.storage_barcode_sticker', [
            'barcode' => $service->getStorageBarcode($marketplace_item),
            'item' => $marketplace_item->item,
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

}
