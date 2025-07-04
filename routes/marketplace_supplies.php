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

});
