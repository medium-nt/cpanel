<?php

use App\Models\MarketplaceOrderItem;

Route::prefix('/marketplace_order_items')->group(function () {
    Route::get('', [App\Http\Controllers\MarketplaceOrderItemController::class, 'index'])
        ->can('viewAny', MarketplaceOrderItem::class)
        ->name('marketplace_order_items.index');

    Route::get('/{marketplace_order_items}/edit', [App\Http\Controllers\MarketplaceOrderItemController::class, 'edit'])
        ->can('update', 'marketplace_order_items')
        ->name('marketplace_order_items.edit');

    Route::put('/startWork/{marketplace_order_item}', [App\Http\Controllers\MarketplaceOrderItemController::class, 'startWork'])
        ->can('update', 'marketplace_order_item')
        ->name('marketplace_order_items.startWork');

    Route::put('/done/{marketplace_order_item}', [App\Http\Controllers\MarketplaceOrderItemController::class, 'done'])
        ->can('update', 'marketplace_order_item')
        ->name('marketplace_order_items.done');

    Route::put('/cancel/{marketplace_order_item}', [App\Http\Controllers\MarketplaceOrderItemController::class, 'cancel'])
        ->can('update', 'marketplace_order_item')
        ->name('marketplace_order_items.cancel');
});

