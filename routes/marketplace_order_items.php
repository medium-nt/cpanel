<?php

use App\Http\Controllers\MarketplaceOrderItemController;
use App\Models\MarketplaceOrderItem;

Route::prefix('/marketplace_order_items')->group(function () {
    Route::get('', [MarketplaceOrderItemController::class, 'index'])
        ->can('viewAny', MarketplaceOrderItem::class)
        ->name('marketplace_order_items.index');

    Route::get('/get_new', [MarketplaceOrderItemController::class, 'getNewOrderItem'])
        ->can('getNew', MarketplaceOrderItem::class)
        ->name('marketplace_order_items.getNewOrderItem');

    Route::put('/labeling/{marketplace_order_item}', [MarketplaceOrderItemController::class, 'labeling'])
        ->can('update', 'marketplace_order_item')
        ->name('marketplace_order_items.labeling');

    Route::put('/complete_cutting/{marketplace_order_item}', [MarketplaceOrderItemController::class, 'completeCutting'])
        ->can('update', 'marketplace_order_item')
        ->name('marketplace_order_items.completeCutting');

    Route::put('/cancel/{marketplace_order_item}', [MarketplaceOrderItemController::class, 'cancel'])
        ->can('update', 'marketplace_order_item')
        ->name('marketplace_order_items.cancel');

    Route::get('/print_cutting', [MarketplaceOrderItemController::class, 'printCutting'])
        ->can('printA4', MarketplaceOrderItem::class)
        ->name('marketplace_order_items.printCutting');
});
