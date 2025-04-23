<?php

use App\Models\MarketplaceOrder;

Route::prefix('/marketplace_orders')->group(function () {
    Route::get('', [App\Http\Controllers\MarketplaceOrderController::class, 'index'])
        ->can('viewAny', MarketplaceOrder::class)
        ->name('marketplace_orders.index');

    Route::get('/create', [App\Http\Controllers\MarketplaceOrderController::class, 'create'])
        ->can('create', MarketplaceOrder::class)
        ->name('marketplace_orders.create');

    Route::post('/store', [App\Http\Controllers\MarketplaceOrderController::class, 'store'])
        ->can('create', MarketplaceOrder::class)
        ->name('marketplace_orders.store');

    Route::get('/{marketplace_order}/edit', [App\Http\Controllers\MarketplaceOrderController::class, 'edit'])
        ->can('update', 'marketplace_order')
        ->name('marketplace_orders.edit');

    Route::put('/update/{marketplace_order}', [App\Http\Controllers\MarketplaceOrderController::class, 'update'])
        ->can('update', 'marketplace_order')
        ->name('marketplace_orders.update');

    Route::get('/{marketplace_order}/complete', [App\Http\Controllers\MarketplaceOrderController::class, 'complete'])
        ->can('complete', 'marketplace_order')
        ->name('marketplace_orders.complete');

    Route::delete('/delete/{marketplace_order}', [App\Http\Controllers\MarketplaceOrderController::class, 'destroy'])
        ->can('delete', 'marketplace_order')
        ->name('marketplace_orders.destroy');
});
