<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\Sku;
use App\Services\MarketplaceApiService;

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
            1 => $service->getBarcodeOzon($orderId),
            2 => $service->getBarcodeWb($orderId),
            default => null,
        };

        $order->is_printed = true;
        $order->save();

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

        return $service->getBarcodeOzonFBO($order);
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
