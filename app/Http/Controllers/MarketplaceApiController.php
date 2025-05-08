<?php

namespace App\Http\Controllers;

use App\Models\Sku;
use App\Services\MarketplaceApiService;

class MarketplaceApiController extends Controller
{
    public function checkSkuz()
    {
        $allItems = MarketplaceApiService::getAllItems();

        $notFoundSkus = MarketplaceApiService::getNotFoundSkus($allItems);

        return view('marketplace_api.check_skuz', [
            'title' => 'Материалы не найденные в ERP',
            'skuz' => $notFoundSkus,
        ]);
    }

    public function checkDuplicateSkuz()
    {
        $duplicates = Sku::selectRaw('sku, count(*) as occurrences')
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
        //
    }
}
