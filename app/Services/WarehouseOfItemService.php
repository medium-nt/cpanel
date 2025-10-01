<?php

namespace App\Services;

use App\Models\MarketplaceOrderItem;
use Illuminate\Database\Eloquent\Builder;

class WarehouseOfItemService
{
    public static function getFiltered($request): Builder
    {
        $items = MarketplaceOrderItem::query()
            ->join('marketplace_items', 'marketplace_order_items.marketplace_item_id', '=', 'marketplace_items.id');

        if ($request->has('status')) {
            $items = $items->where('marketplace_order_items.status', $request->status);
        } else {
            $items = $items->whereIn('marketplace_order_items.status', [9, 10, 11, 12]);
        }

        if ($request->has('material')) {
            $items = $items->where('marketplace_items.title', 'like', '%' . $request->material . '%');
        }

        if ($request->has('width')) {
            $items = $items->where('marketplace_items.width', $request->width);
        }

        if ($request->has('height')) {
            $items = $items->where('marketplace_items.height', $request->height);
        }

        if ($request->has('shelf')) {
            $items = $items->where('marketplace_order_items.shelf_id', $request->shelf);
        }

        return $items
            ->select('marketplace_order_items.*', 'marketplace_items.title', 'marketplace_items.width', 'marketplace_items.height');
    }

    public static function getStorageBarcode(MarketplaceOrderItem $marketplace_item)
    {
        $barcode = $marketplace_item->storage_barcode;

        if (!$barcode) {
            $barcode = self::generateBarcode($marketplace_item->id);

            $marketplace_item->storage_barcode = $barcode;
            $marketplace_item->save();
        }

        return $barcode;
    }

    private static function generateBarcode(int $id): string
    {
        //  используется алгоритм Луна.
        $base = str_pad($id, 8, '0', STR_PAD_LEFT);
        $sum = 0;

        foreach (str_split(strrev($base)) as $i => $digit) {
            $n = $digit * ($i % 2 === 0 ? 2 : 1);
            $sum += $n > 9 ? $n - 9 : $n;
        }

        return $base . ((10 - $sum % 10) % 10);
    }
}
