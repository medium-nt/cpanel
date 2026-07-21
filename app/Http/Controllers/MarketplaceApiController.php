<?php

namespace App\Http\Controllers;

use App\Models\Marketplace;
use App\Models\MarketplaceOrder;
use App\Models\ProductSticker;
use App\Models\Sku;
use App\Models\User;
use App\Services\MarketplaceApiService;
use App\Services\StickerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class MarketplaceApiController extends Controller
{
    public function checkSkuz()
    {
        $allItemsOzon = MarketplaceApiService::getAllItemsOzon();
        $allItemsWb = MarketplaceApiService::getAllItemsWb();
        $allItems = array_merge($allItemsOzon, $allItemsWb);

        $notFoundSkus = MarketplaceApiService::getNotFoundSkus($allItems);

        return view('marketplace_api.check_skuz', [
            'title' => 'Материалы не найденные в ERP',
            'skuz' => $notFoundSkus,
        ]);
    }

    public function checkDuplicateSkuz()
    {
        $duplicates = Sku::query()->selectRaw('sku, count(*) as occurrences')
            ->groupBy('sku')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        return view('marketplace_api.check_duplicate_skuz', [
            'title' => 'Дубли SKU в ERP',
            'duplicates' => $duplicates,
        ]);
    }

    public function uploadingNewProducts()
    {
        $result = MarketplaceApiService::uploadingNewProducts();

        return view('marketplace_api.uploading_new_products', [
            'title' => 'Отчет о загрузке новых товаров',
            'results' => $result,
        ]);
    }

    public function uploadingCancelledProducts()
    {
        $result = MarketplaceApiService::uploadingCancelledProducts();

        return view('marketplace_api.uploading_cancelled_products', [
            'title' => 'Отчет о загрузке отмененных заявок',
            'results' => $result,
        ]);
    }

    public function getBarcodeFile(MarketplaceApiService $service)
    {
        $orderId = request()->marketplaceOrderId;

        $order = MarketplaceOrder::query()
            ->where('order_id', $orderId)
            ->first();

        if (! $order) {
            ob_start();
            echo 'Нет заказа с таким номером!';
            $output = ob_get_clean();

            return response($output);
        }

        $result = match ($order->marketplace_id) {
            Marketplace::OZON => $service->getBarcodeOzon($orderId),
            Marketplace::WB => $service->getBarcodeWb($orderId),
            default => null,
        };

        $order->is_printed = true;
        $order->save();

        $user = User::find(session('user_id'));
        if ($user) {
            Log::channel('orders')
                ->info('Стикер заказа '.$orderId.' был распечатан сотрудником '.$user->name);
        }

        return $result;
    }

    public function getFBOBarcodeFile(MarketplaceApiService $service)
    {
        $orderId = request()->marketplaceOrderId;

        $order = MarketplaceOrder::query()
            ->where('order_id', $orderId)
            ->first();

        if (! $order) {
            echo 'Нет заказа с таким номером!';
            exit;
        }

        $order->is_printed = true;
        $order->save();

        $user = User::find(session('user_id'));
        if ($user) {
            Log::channel('orders')
                ->info('Стикер заказа '.$orderId.' был распечатан сотрудником '.$user->name);
        }

        $itemTitle = $order->items->first()?->item?->title ?? '';

        if (StickerService::resolveTemplate($itemTitle, $order->marketplace_id) === 'pdf.fbo_sticker') {
            return $this->generateNewSticker($order);
        }

        return match ($order->marketplace_id) {
            Marketplace::OZON => $service->getBarcodeOzonFBO(collect([$order])),
            Marketplace::WB => $service->getBarcodeWBFBO(collect([$order])),
            default => null,
        };
    }

    /**
     * Генерирует PDF-стикер нового формата (120×75) для товара со специальным title.
     */
    private function generateNewSticker(MarketplaceOrder $order): \Illuminate\Http\Response
    {
        $item = $order->items->first()->item;
        $sku = Sku::query()
            ->where('item_id', $item->id)
            ->where('marketplace_id', $order->marketplace_id)
            ->first();

        $barcode = ($order->marketplace_id == Marketplace::OZON)
            ? MarketplaceApiService::getBarcodeOzonBySku($sku->sku)
            : $sku->sku;

        $productSticker = ProductSticker::query()
            ->where('title', $item->title)
            ->first();

        $stickers = [[
            'barcode' => $barcode,
            'item' => $item,
            'order' => $order,
            'fontSizeCluster' => StickerService::resolveFontSizeCluster($order->cluster, 'pdf.fbo_sticker'),
            'seamstressId' => $order->items[0]->seamstress?->id,
            'cutterId' => $order->items[0]->cutter?->id,
            'article' => ($order->marketplace_id == Marketplace::WB)
                ? MarketplaceApiService::getItemWbBySku($sku->sku)?->nmID ?? ''
                : '',
            'color' => $productSticker?->color ?? '',
            'country' => $productSticker?->country ?? '',
            'material' => $productSticker?->material ?? '',
            'fastening_type' => $productSticker?->fastening_type ?? '',
            'marketplace_id' => $order->marketplace_id,
        ]];

        $pdf = Pdf::loadView('pdf.fbo_sticker', ['stickers' => $stickers]);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('sticker.pdf');
    }

    public function getFBOBarcodeHtml(MarketplaceApiService $service)
    {
        $orderId = request()->marketplaceOrderId;

        $order = MarketplaceOrder::query()
            ->where('order_id', $orderId)
            ->first();

        if (! $order) {
            echo 'Нет заказа с таким номером!';
            exit;
        }

        $order->is_printed = true;
        $order->save();

        return $service->getBarcodeOzonFBOHtml($order);
    }
}
