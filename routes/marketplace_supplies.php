<?php

use App\Models\MarketplaceSupply;

Route::prefix('/marketplace_supplies')->group(function () {
    Route::get('', [App\Http\Controllers\MarketplaceSupplyController::class, 'index'])
        ->can('viewAny', MarketplaceSupply::class)
        ->name('marketplace_supplies.index');

    Route::get('/show/{marketplace_supply}', [App\Http\Controllers\MarketplaceSupplyController::class, 'show'])
        ->can('view', 'marketplace_supply')
        ->name('marketplace_supplies.show');

    Route::get('/{marketplace_supply}/complete', [App\Http\Controllers\MarketplaceSupplyController::class, 'complete'])
        ->can('complete', 'marketplace_supply')
        ->name('marketplace_supplies.complete');

    Route::get('/{marketplace_id}/create', [App\Http\Controllers\MarketplaceSupplyController::class, 'create'])
        ->can('create', MarketplaceSupply::class)
        ->name('marketplace_supplies.create');

    Route::delete('/{marketplace_supply}/destroy', [App\Http\Controllers\MarketplaceSupplyController::class, 'destroy'])
        ->can('destroy', 'marketplace_supply')
        ->name('marketplace_supplies.destroy');

    Route::get('/{marketplace_supply}/get_docs', [App\Http\Controllers\MarketplaceSupplyController::class, 'getDocs'])
        ->can('complete', 'marketplace_supply')
        ->name('marketplace_supplies.get_docs');

    Route::get('/{marketplace_supply}/get_barcode', [App\Http\Controllers\MarketplaceSupplyController::class, 'getBarcode'])
        ->can('complete', 'marketplace_supply')
        ->name('marketplace_supplies.get_barcode');

    Route::get('/{marketplace_supply}/update_status_orders', [App\Http\Controllers\MarketplaceSupplyController::class, 'updateStatusOrders'])
        ->can('complete', 'marketplace_supply')
        ->name('marketplace_supplies.update_status_orders');

    Route::get('/{marketplace_supply}/done', [App\Http\Controllers\MarketplaceSupplyController::class, 'done'])
        ->can('complete', 'marketplace_supply')
        ->name('marketplace_supplies.done');

    Route::put('/{marketplace_supply}/download_video', [App\Http\Controllers\MarketplaceSupplyController::class, 'download_video'])
        ->can('download_video', 'marketplace_supply')
        ->name('marketplace_supplies.download_video');

    Route::get('/{marketplace_supply}/delete_video', [App\Http\Controllers\MarketplaceSupplyController::class, 'delete_video'])
        ->can('delete_video', 'marketplace_supply')
        ->name('marketplace_supplies.delete_video');

});
