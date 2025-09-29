<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrderItem;
use App\Models\Shelf;
use App\Services\MarketplaceApiService;
use App\Services\MarketplaceItemService;
use App\Services\WarehouseOfItemService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class WarehouseOfItemController extends Controller
{
    public function index(Request $request)
    {
        return view('warehouse_of_item.index', [
            'title' => 'Склад товара',
            'materials' => MarketplaceItemService::getAllTitleMaterials(),
            'widths' => MarketplaceItemService::getAllWidthMaterials(),
            'heights' => MarketplaceItemService::getAllHeightMaterials(),
            'totalItems' => WarehouseOfItemService::getFiltered($request)->count(),
            'items' => WarehouseOfItemService::getFiltered($request)
                ->paginate(20),
        ]);
    }

    public function newRefunds(Request $request)
    {
        $barcode = $request->barcode;

        $marketplace_item = null;
        if ($barcode) {
            if (mb_strlen(trim($barcode)) == 15) {
                $barcode = MarketplaceApiService::getOzonPostingNumberByBarcode($barcode);
            }

            if (mb_strlen(trim($barcode)) == 13 || mb_strlen(trim($barcode)) == 12) {
                $barcode = MarketplaceApiService::getOzonPostingNumberByReturnBarcode($barcode);
            }

            $marketplace_item = MarketplaceOrderItem::query()
                ->join('marketplace_orders', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
                ->with('item')
                //  TO_DO: вернуть фильтр по статусу
                //  ->whereIn('marketplace_order_items.status', [10])
                ->where(function ($query) use ($barcode) {
                    $query->where('marketplace_orders.order_id', $barcode)
                        ->orWhere('marketplace_order_items.storage_barcode', $barcode)
                        ->orWhere('part_b', $barcode)
                        ->orWhere('barcode', $barcode);
                })->select('marketplace_order_items.*')
                ->get();

            if ($marketplace_item->isEmpty()) {
                $message = 'Нет такого заказа.';
            }

            if ($marketplace_item->count() > 1) {
                $message = 'Найдено несколько заказов. Выберите нужный:';
                $marketplaceItems = $marketplace_item;
            }

            if ($marketplace_item->count() == 1) {
                $marketplace_item = $marketplace_item->first();
                $returnReason = MarketplaceApiService::getReturnReason($marketplace_item);
            }
        } else {
            $message = 'Введите штрихкод.';
        }

        return view('warehouse_of_item.new_refunds', [
            'title' => 'Товар с возврата',
            'marketplace_item' => $marketplace_item,
            'marketplace_items' => $marketplaceItems ?? [],
            'barcode' => $request->barcode ?? '',
            'message' => $message ?? '',
            'shelves' => Shelf::all(),
            'returnReason' => $returnReason ?? '',
        ]);
    }

    public static function getStorageBarcodeFile(MarketplaceOrderItem $marketplace_item)
    {
        $barcode = WarehouseOfItemService::getStorageBarcode($marketplace_item);

        $pdf = PDF::loadView('pdf.storage_barcode_sticker', [
            'barcode' => $barcode,
            'item' => $marketplace_item->item,
            'seamstressName' => $marketplace_item->marketplaceOrder->items[0]->seamstress->name
        ]);

        $pdf->setPaper('A4', 'portrait');
        return $pdf->stream('barcode.pdf');
    }

    public function saveStorage(MarketplaceOrderItem $marketplace_item)
    {
        if (!$marketplace_item->storage_barcode) {
            return redirect()
                ->route('warehouse_of_item.new_refunds',
                    ['barcode' => $marketplace_item->marketplaceOrder->order_id])
                ->with('error', 'Не распечатан штрихкод хранения');
        }

        if (!request('shelf_id')) {
            return redirect()
                ->route('warehouse_of_item.new_refunds',
                    ['barcode' => $marketplace_item->marketplaceOrder->order_id])
                ->with('error', 'Не указан номер полки');
        }

        $marketplace_item->shelf_id = request('shelf_id');
        $marketplace_item->status = 11;
        $marketplace_item->save();

        return redirect()
            ->route('warehouse_of_item.index')
            ->with('success', 'Изменения сохранены');
    }

}
