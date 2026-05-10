<?php

use App\Http\Controllers\MarketplaceSupplyController;
use App\Http\Controllers\SupplyBoxController;
use App\Models\MarketplaceSupply;

Route::prefix('/marketplace_supplies')->group(function () {
    Route::get('', [MarketplaceSupplyController::class, 'index'])
        ->can('viewAny', MarketplaceSupply::class)
        ->name('marketplace_supplies.index');

    Route::get('/show/{marketplace_supply}', [MarketplaceSupplyController::class, 'show'])
        ->can('view', 'marketplace_supply')
        ->name('marketplace_supplies.show');

    Route::post('/{marketplace_supply}/link-wb-fbo', [MarketplaceSupplyController::class, 'linkWbFbo'])
        ->can('linkWbFbo', 'marketplace_supply')
        ->name('marketplace_supplies.link_wb_fbo');

    Route::get('/{marketplace_supply}/load-fbo-goods', [MarketplaceSupplyController::class, 'loadFboGoods'])
        ->can('view', 'marketplace_supply')
        ->name('marketplace_supplies.load_fbo_goods');

    Route::post('/{marketplace_supply}/confirm-fbo-goods', [MarketplaceSupplyController::class, 'confirmFboGoods'])
        ->can('updateWbFbo', 'marketplace_supply')
        ->name('marketplace_supplies.confirm_fbo_goods');

    Route::get('/{marketplace_supply}/boxes', [SupplyBoxController::class, 'index'])
        ->can('manageBoxes', 'marketplace_supply')
        ->name('supply_boxes.index');

    Route::post('/{marketplace_supply}/boxes', [SupplyBoxController::class, 'store'])
        ->can('manageBoxes', 'marketplace_supply')
        ->name('supply_boxes.store');

    Route::get('/{marketplace_supply}/boxes/{box}', [SupplyBoxController::class, 'show'])
        ->can('manageBoxes', 'marketplace_supply')
        ->name('supply_boxes.show');

    Route::delete('/{marketplace_supply}/boxes/{box}', [SupplyBoxController::class, 'destroy'])
        ->can('manageBoxes', 'marketplace_supply')
        ->name('supply_boxes.destroy');

    Route::delete('/{marketplace_supply}/boxes/{box}/remove-order/{order}', [SupplyBoxController::class, 'removeOrder'])
        ->can('manageBoxes', 'marketplace_supply')
        ->name('supply_boxes.remove_order');

    Route::get('/{marketplace_supply}/edit-wb-fbo', [MarketplaceSupplyController::class, 'editWbFbo'])
        ->can('updateWbFbo', 'marketplace_supply')
        ->name('marketplace_supplies.edit_wb_fbo');

    Route::put('/{marketplace_supply}/update-wb-fbo', [MarketplaceSupplyController::class, 'updateWbFbo'])
        ->can('updateWbFbo', 'marketplace_supply')
        ->name('marketplace_supplies.update_wb_fbo');

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
