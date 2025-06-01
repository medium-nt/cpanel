<?php

use App\Models\MarketplaceOrderItem;

Route::prefix('/marketplace_order_items')->group(function () {
    Route::get('', [App\Http\Controllers\MarketplaceOrderItemController::class, 'index'])
        ->can('viewAny', MarketplaceOrderItem::class)
        ->name('marketplace_order_items.index');

/*    Route::put('/startWork/{marketplace_order_item}', [App\Http\Controllers\MarketplaceOrderItemController::class, 'startWork'])
        ->can('update', 'marketplace_order_item')
        ->name('marketplace_order_items.startWork');*/

    Route::get('/get_new', [App\Http\Controllers\MarketplaceOrderItemController::class, 'getNewOrderItem'])
        ->can('getNew', MarketplaceOrderItem::class)
        ->name('marketplace_order_items.getNewOrderItem');

    Route::put('/labeling/{marketplace_order_item}', [App\Http\Controllers\MarketplaceOrderItemController::class, 'labeling'])
        ->can('update', 'marketplace_order_item')
        ->name('marketplace_order_items.labeling');

    Route::put('/cancel/{marketplace_order_item}', [App\Http\Controllers\MarketplaceOrderItemController::class, 'cancel'])
        ->can('update', 'marketplace_order_item')
        ->name('marketplace_order_items.cancel');
});

