<?php

namespace App\Services;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Sku;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WarehouseOfItemService
{
    public function getFiltered(Request $request): Builder
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

    public function getStorageBarcode(MarketplaceOrderItem $marketplace_item): string
    {
        $barcode = $marketplace_item->storage_barcode;

        if (!$barcode) {
            $barcode = $this->generateBarcode($marketplace_item->id);

            $marketplace_item->storage_barcode = $barcode;
            $marketplace_item->save();
        }

        return $barcode;
    }

    private function generateBarcode(int $id): string
    {
        //  используется алгоритм Луна.
        $base = str_pad($id, 8, '0', STR_PAD_LEFT);
        $sum = 0;

        foreach (str_split(strrev($base)) as $i => $digit) {
            $n = (int)$digit * ($i % 2 === 0 ? 2 : 1);
            $sum += $n > 9 ? $n - 9 : $n;
        }

        return $base . ((10 - $sum % 10) % 10);
    }

    public function saveItemToStorage(MarketplaceOrderItem $item, int $shelfId): void
    {
        $item->shelf_id = $shelfId;
        $item->status = 11;
        $item->save();

        MarketplaceOrder::query()
            ->where('id', $item->marketplace_order_id)
            ->update([
                'returned_at' => now(),
                'status' => 9
            ]);
    }

    public function findRefundItemByBarcode($barcode): array
    {
        if (!$barcode) {
            return [
                'message' => 'Введите штрихкод',
                'marketplace_item' => null,
                'marketplace_items' => collect(),
                'returnReason' => '',
            ];
        }

        // если это стикер OZON FBS
        if (!is_array($barcode) && mb_strlen(trim($barcode)) == 15) {
            $barcode = MarketplaceApiService::getOzonPostingNumberByBarcode($barcode);
        }

        // если это стикер OZON возврат
        if (!is_array($barcode) && str_starts_with(trim($barcode), 'ii')) {
            $barcode = MarketplaceApiService::getOzonPostingNumberByReturnBarcode($barcode);
        }

        $isFBO = false;

        // если это стикер OZON FBO
        if (!is_array($barcode) && str_starts_with(trim($barcode), 'OZN')) {
            $sku = trim($barcode, 'OZN');

            $barcode = Sku::query()->where('sku', $sku)
                ->first()?->item->id ?? '-';

            $isFBO = true;
        }

        $items = MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
            ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
            ->with('item')
            //  TO_DO: вернуть фильтр по статусу
//                  ->whereIn('marketplace_order_items.status', [10])
            ->whereIn('marketplace_order_items.status', [3, 11])
            ->where(function ($query) use ($barcode) {
                $query->where('marketplace_orders.order_id', $barcode)
                    ->orWhere('marketplace_order_items.storage_barcode', $barcode)
                    ->orWhere('part_b', $barcode)
                    ->orWhere('barcode', $barcode)
                    ->orWhere('marketplace_items.id', $barcode);
            })->when($isFBO, function ($query) {
                $query->where('marketplace_orders.fulfillment_type', 'FBO');
            })->select('marketplace_order_items.*')
            ->get();

        if ($items->isEmpty()) {
            return [
                'message' => 'Нет такого заказа',
                'marketplace_item' => null,
                'marketplace_items' => collect(),
                'returnReason' => '',
            ];
        }

        if ($items->count() > 1) {
            return [
                'message' => 'Найдено несколько заказов. Выберите нужный:',
                'marketplace_item' => null,
                'marketplace_items' => $items,
                'returnReason' => '',
            ];
        }

        $item = $items->first();

        if ($item->status == 11) {
            return [
                'message' => 'Товар уже находится на складе',
                'marketplace_item' => null,
                'marketplace_items' => collect(),
                'returnReason' => '',
            ];
        }

        $returnReason = MarketplaceApiService::getReturnReason($item);

        return [
            'message' => '',
            'marketplace_item' => $item,
            'marketplace_items' => collect(),
            'returnReason' => $returnReason,
        ];
    }

    public function getCreateItems($validatedData, MarketplaceItem $item): array
    {
        $marketplaceItems = [];

        for ($i = 0; $i < $validatedData['quantity']; $i++) {

            $marketplaceOrder = MarketplaceOrder::query()->create([
                'order_id' => '...',
                'marketplace_id' => 1,
                'fulfillment_type' => 'FBO',
                'status' => 9,
                'completed_at' => now(),
                'returned_at' => now(),
                'created_at' => now(),
            ]);

            $marketplaceOrderItem = MarketplaceOrderItem::query()->create([
                'marketplace_order_id' => $marketplaceOrder->id,
                'marketplace_item_id' => $item->id,
                'shelf_id' => $validatedData['shelf_id'],
                'quantity' => 1,
                'price' => 0,
                'status' => 11,
                'seamstress_id' => 3,
                'completed_at' => now()->startOfDay()->subDays(2),
                'created_at' => Carbon::parse($marketplaceOrder->created_at),
            ]);

            $marketplaceOrderItem->storage_barcode = $this->getStorageBarcode($marketplaceOrderItem);
            $marketplaceOrderItem->save();

            $marketplaceOrder->order_id = 'под товар ' . $marketplaceOrderItem->id;
            $marketplaceOrder->save();

            $marketplaceItems[] = $marketplaceOrderItem->id;
        }

        return $marketplaceItems;
    }
}
