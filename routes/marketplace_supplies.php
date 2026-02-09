<?php

use App\Http\Controllers\MarketplaceSupplyController;
use App\Models\MarketplaceSupply;

Route::prefix('/marketplace_supplies')->group(function () {
    Route::get('', [MarketplaceSupplyController::class, 'index'])
        ->can('viewAny', MarketplaceSupply::class)
        ->name('marketplace_supplies.index');

    Route::get('/show/{marketplace_supply}', [MarketplaceSupplyController::class, 'show'])
        ->can('view', 'marketplace_supply')
        ->name('marketplace_supplies.show');

    Route::get('/{marketplace_supply}/complete', [MarketplaceSupplyController::class, 'complete'])
        ->can('complete', 'marketplace_supply')
        ->name('marketplace_supplies.complete');

    Route::get('/{marketplace_id}/create', [MarketplaceSupplyController::class, 'create'])
        ->can('create', MarketplaceSupply::class)
        ->name('marketplace_supplies.create');

    Route::delete('/{marketplace_supply}/destroy', [MarketplaceSupplyController::class, 'destroy'])
        ->can('destroy', 'marketplace_supply')
        ->name('marketplace_supplies.destroy');

    Route::get('/{marketplace_supply}/get_docs', [MarketplaceSupplyController::class, 'getDocs'])
        ->can('complete', 'marketplace_supply')
        ->name('marketplace_supplies.get_docs');

    Route::get('/{marketplace_supply}/get_barcode', [MarketplaceSupplyController::class, 'getBarcode'])
        ->can('complete', 'marketplace_supply')
        ->name('marketplace_supplies.get_barcode');

    Route::get('/{marketplace_supply}/update_status_orders', [MarketplaceSupplyController::class, 'updateStatusOrders'])
        ->can('complete', 'marketplace_supply')
        ->name('marketplace_supplies.update_status_orders');

    Route::get('/{marketplace_supply}/done', [MarketplaceSupplyController::class, 'done'])
        ->can('complete', 'marketplace_supply')
        ->name('marketplace_supplies.done');

    Route::get('/{marketplace_supply}/close', [MarketplaceSupplyController::class, 'close'])
        ->can('close', 'marketplace_supply')
        ->name('marketplace_supplies.close');

    Route::get('/{marketplace_supply}/delete_video', [MarketplaceSupplyController::class, 'delete_video'])
        ->can('delete_video', 'marketplace_supply')
        ->name('marketplace_supplies.delete_video');

    Route::post('/upload-chunk', [MarketplaceSupplyController::class, 'chunkedUpload'])
        ->name('marketplace_supplies.upload-chunk');

});
