<?php

use App\Models\MarketplaceItem;

Route::prefix('/marketplace_items')->group(function () {
    Route::get('', [App\Http\Controllers\MarketplaceItemController::class, 'index'])
        ->can('viewAny', MarketplaceItem::class)
        ->name('marketplace_items.index');

    Route::get('/create', [App\Http\Controllers\MarketplaceItemController::class, 'create'])
        ->can('create', MarketplaceItem::class)
        ->name('marketplace_items.create');

    Route::post('/store', [App\Http\Controllers\MarketplaceItemController::class, 'store'])
        ->can('create', MarketplaceItem::class)
        ->name('marketplace_items.store');

    Route::get('/{marketplace_item}/edit', [App\Http\Controllers\MarketplaceItemController::class, 'edit'])
        ->can('update', 'marketplace_item')
        ->name('marketplace_items.edit');

    Route::put('/update/{marketplace_item}', [App\Http\Controllers\MarketplaceItemController::class, 'update'])
        ->can('update', 'marketplace_item')
        ->name('marketplace_items.update');

    Route::delete('/delete/{marketplace_item}', [App\Http\Controllers\MarketplaceItemController::class, 'destroy'])
        ->can('delete', 'marketplace_item')
        ->name('marketplace_items.destroy');
});
