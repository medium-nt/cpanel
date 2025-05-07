<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
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

    public function uploadingNewProducts()
    {
        //
    }
}
