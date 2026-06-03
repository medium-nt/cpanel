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
        ->can('updateFbo', 'marketplace_supply')
        ->name('marketplace_supplies.confirm_fbo_goods');

    Route::post('/{marketplace_supply}/link-ozon-fbo', [MarketplaceSupplyController::class, 'linkOzonFbo'])
        ->can('linkOzonFbo', 'marketplace_supply')
        ->name('marketplace_supplies.link_ozon_fbo');

    Route::get('/{marketplace_supply}/load-ozon-fbo-goods', [MarketplaceSupplyController::class, 'loadOzonFboGoods'])
        ->can('view', 'marketplace_supply')
        ->name('marketplace_supplies.load_ozon_fbo_goods');

    Route::post('/{marketplace_supply}/confirm-ozon-fbo-goods', [MarketplaceSupplyController::class, 'confirmOzonFboGoods'])
        ->can('updateFbo', 'marketplace_supply')
        ->name('marketplace_supplies.confirm_ozon_fbo_goods');

    Route::get('/{marketplace_supply}/boxes', [SupplyBoxController::class, 'index'])
        ->can('viewBoxes', 'marketplace_supply')
        ->name('supply_boxes.index');

    Route::post('/{marketplace_supply}/boxes', [SupplyBoxController::class, 'store'])
        ->can('manageBoxes', 'marketplace_supply')
        ->name('supply_boxes.store');

    Route::get('/{marketplace_supply}/boxes/export-excel', [SupplyBoxController::class, 'exportExcel'])
        ->can('exportBoxes', 'marketplace_supply')
        ->name('supply_boxes.export_excel');

    Route::post('/{marketplace_supply}/boxes/mark-assembled', [SupplyBoxController::class, 'markAssembled'])
        ->can('manageBoxes', 'marketplace_supply')
        ->name('supply_boxes.mark_assembled');

    Route::get('/{marketplace_supply}/boxes/{box}', [SupplyBoxController::class, 'show'])
        ->can('viewBoxes', 'marketplace_supply')
        ->name('supply_boxes.show');

    Route::delete('/{marketplace_supply}/boxes/{box}', [SupplyBoxController::class, 'destroy'])
        ->can('manageBoxes', 'marketplace_supply')
        ->name('supply_boxes.destroy');

    Route::post('/{marketplace_supply}/boxes/{box}/close', [SupplyBoxController::class, 'closeBox'])
        ->can('manageBoxes', 'marketplace_supply')
        ->name('supply_boxes.close_box');

    Route::get('/{marketplace_supply}/boxes/{box}/print-sticker', [SupplyBoxController::class, 'printSticker'])
        ->can('manageBoxes', 'marketplace_supply')
        ->name('supply_boxes.print_sticker');

    Route::delete('/{marketplace_supply}/boxes/{box}/remove-order/{order}', [SupplyBoxController::class, 'removeOrder'])
        ->can('manageBoxes', 'marketplace_supply')
        ->name('supply_boxes.remove_order');

    Route::get('/{marketplace_supply}/edit-fbo', [MarketplaceSupplyController::class, 'editFbo'])
        ->can('updateFbo', 'marketplace_supply')
        ->name('marketplace_supplies.edit_fbo');

    Route::put('/{marketplace_supply}/update-fbo', [MarketplaceSupplyController::class, 'updateFbo'])
        ->can('updateFbo', 'marketplace_supply')
        ->name('marketplace_supplies.update_fbo');

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

    Route::post('/{marketplace_supply}/upload-sticker', [MarketplaceSupplyController::class, 'uploadSticker'])
        ->can('manageSticker', 'marketplace_supply')
        ->name('marketplace_supplies.upload_sticker');

    Route::get('/{marketplace_supply}/download-sticker', [MarketplaceSupplyController::class, 'downloadSticker'])
        ->can('downloadSticker', 'marketplace_supply')
        ->name('marketplace_supplies.download_sticker');

    Route::get('/{marketplace_supply}/delete-sticker', [MarketplaceSupplyController::class, 'deleteSticker'])
        ->can('manageSticker', 'marketplace_supply')
        ->name('marketplace_supplies.delete_sticker');

    Route::post('/{marketplace_supply}/upload-gazelka-invoice', [MarketplaceSupplyController::class, 'uploadGazelkaInvoice'])
        ->can('manageGazelkaInvoice', 'marketplace_supply')
        ->name('marketplace_supplies.upload_gazelka_invoice');

    Route::get('/{marketplace_supply}/download-gazelka-invoice', [MarketplaceSupplyController::class, 'downloadGazelkaInvoice'])
        ->can('downloadGazelkaInvoice', 'marketplace_supply')
        ->name('marketplace_supplies.download_gazelka_invoice');

    Route::get('/{marketplace_supply}/delete-gazelka-invoice', [MarketplaceSupplyController::class, 'deleteGazelkaInvoice'])
        ->can('manageGazelkaInvoice', 'marketplace_supply')
        ->name('marketplace_supplies.delete_gazelka_invoice');

    Route::get('/{marketplace_supply}/mark-shipped', [MarketplaceSupplyController::class, 'markShipped'])
        ->can('complete', 'marketplace_supply')
        ->name('marketplace_supplies.mark_shipped');

    Route::post('/upload-chunk', [MarketplaceSupplyController::class, 'chunkedUpload'])
        ->name('marketplace_supplies.upload-chunk');

});
