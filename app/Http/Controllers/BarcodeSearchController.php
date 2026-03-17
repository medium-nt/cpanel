<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrderItem;
use App\Models\Sku;
use App\Services\MarketplaceApiService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarcodeSearchController extends Controller
{
    public function index(Request $request): View
    {
        $barcode = $request->query('barcode');
        $result = $this->findItemByBarcode($barcode);

        return view('barcode_search.index', [
            'title' => 'Поиск по штрихкоду',
            'barcode' => $barcode,
            'message' => $result['message'],
            'order' => $result['order'],
            'item' => $result['item'],
        ]);
    }

    public function findItemByBarcode($barcode): array
    {
        if (! $barcode) {
            return [
                'message' => 'Введите штрихкод',
                'order' => null,
                'item' => null,
            ];
        }

        $marketplaceOrderNumber = $sku = null;

        $isFBO = false;
        $fboMarketplaceId = null;

        // если это стикер OZON FBS
        if (! is_array($barcode) && mb_strlen(trim($barcode)) == 15) {
            $marketplaceOrderNumber = MarketplaceApiService::getOzonPostingNumberByBarcode($barcode);
        }

        // если это стикер возврата OZON
        if (! is_array($barcode) && str_starts_with(trim($barcode), 'ii')) {
            $marketplaceOrderNumber = MarketplaceApiService::getOzonPostingNumberByReturnBarcode($barcode);
        }

        // Обработка нашего стикера WB FBO (13 символов)
        if (! is_array($barcode) && mb_strlen(trim($barcode)) == 13) {
            $sku = trim($barcode);
            $sku = Sku::query()->where('sku', $sku)->first()?->item->id ?? '-';
            $isFBO = true;
            $fboMarketplaceId = 2; // WB
        }

        // Обработка нашего стикера OZON FBO (начинается с 'OZN')
        if (! is_array($barcode) && str_starts_with(trim($barcode), 'OZN')) {
            $sku = trim($barcode, 'OZN');
            $sku = Sku::query()->where('sku', $sku)->first()?->item->id ?? '-';
            $isFBO = true;
            $fboMarketplaceId = 1; // Ozon
        }

        if ($marketplaceOrderNumber) {
            return [
                'message' => null,
                'order' => $marketplaceOrderNumber,
                'item' => null,
            ];
        }

        if ($sku) {
            return [
                'message' => null,
                'order' => 'товара id: '.$sku,
                'item' => null,
            ];
        }

        $orderItem = MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
            ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
            ->with('item', 'item.material', 'marketplaceOrder')
            ->where(function ($query) use ($barcode, $sku) {
                $query->where('marketplace_orders.order_id', $barcode)
                    ->orWhere('marketplace_order_items.storage_barcode', $barcode)
                    ->orWhere('part_b', $barcode)
                    ->orWhere('barcode', $barcode)
                    ->orWhere('marketplace_items.id', $sku);
            })
            ->when($isFBO, function ($query) use ($fboMarketplaceId) {
                $query->where('marketplace_orders.fulfillment_type', 'FBO')
                    ->where('marketplace_orders.marketplace_id', $fboMarketplaceId);
            })
            ->select('marketplace_order_items.*')
            ->first();

        dd($orderItem);

        if (! $orderItem) {
            return [
                'message' => 'Товар не найден',
                'order' => null,
                'item' => null,
            ];
        }

        return [
            'message' => null,
            'order' => $orderItem->marketplaceOrder,
            'item' => $orderItem,
        ];
    }
}
