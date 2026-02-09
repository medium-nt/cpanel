<?php

use App\Http\Controllers\MarketplaceOrderController;
use App\Models\MarketplaceOrder;

Route::prefix('/marketplace_orders')->group(function () {
    Route::get('', [MarketplaceOrderController::class, 'index'])
        ->can('viewAny', MarketplaceOrder::class)
        ->name('marketplace_orders.index');

    Route::get('/create', [MarketplaceOrderController::class, 'create'])
        ->can('create', MarketplaceOrder::class)
        ->name('marketplace_orders.create');

    Route::post('/store', [MarketplaceOrderController::class, 'store'])
        ->can('create', MarketplaceOrder::class)
        ->name('marketplace_orders.store');

    Route::get('/{marketplace_order}/edit', [MarketplaceOrderController::class, 'edit'])
        ->can('update', 'marketplace_order')
        ->name('marketplace_orders.edit');

    Route::put('/update/{marketplace_order}', [MarketplaceOrderController::class, 'update'])
        ->can('update', 'marketplace_order')
        ->name('marketplace_orders.update');

    Route::get('/{marketplace_order}/complete', [MarketplaceOrderController::class, 'complete'])
        ->can('complete', 'marketplace_order')
        ->name('marketplace_orders.complete');

    Route::delete('/delete/{marketplace_order}', [MarketplaceOrderController::class, 'destroy'])
        ->can('delete', 'marketplace_order')
        ->name('marketplace_orders.destroy');

    Route::delete('/{marketplace_order}/remove', [MarketplaceOrderController::class, 'remove'])
        ->can('remove', 'marketplace_order')
        ->name('marketplace_orders.remove');
});
