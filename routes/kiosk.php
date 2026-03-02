<?php

use App\Http\Controllers\StickerPrintingController;

Route::prefix('/kiosk')->group(function () {
    Route::get('', [StickerPrintingController::class, 'kiosk'])
        ->name('kiosk');
    Route::get('opening_closing_shifts', [StickerPrintingController::class, 'opening_closing_shifts'])
        ->name('opening_closing_shifts');
    Route::get('statistics_reports', [StickerPrintingController::class, 'statisticsReports'])
        ->name('statistics_reports');
    Route::get('product_stickers', [StickerPrintingController::class, 'productStickers'])
        ->name('product_stickers');

    Route::get('defects', [StickerPrintingController::class, 'defects'])
        ->name('defects.create');
    Route::post('defects', [StickerPrintingController::class, 'saveDefects'])
        ->name('defects.store');
    Route::get('defects/print_sticker/{order}', [StickerPrintingController::class, 'printSticker'])
        ->name('defects.print_sticker');

    Route::get('product-label/{materialName}', [StickerPrintingController::class, 'printProductLabel'])
        ->name('kiosk.product-label');

    Route::get('api/roll/{roll_code}', [StickerPrintingController::class, 'getRollByCode'])
        ->name('kiosk.api.roll');

    Route::get('returns', [StickerPrintingController::class, 'returns'])
        ->name('returns');

    Route::get('on_inspection', [StickerPrintingController::class, 'onInspection'])
        ->name('on_inspection');

    Route::get('processed_items', [StickerPrintingController::class, 'processedItems'])
        ->name('kiosk.processed_items');

    Route::post('scan_inspection_item', [StickerPrintingController::class, 'scanInspectionItem'])
        ->name('kiosk.scan_inspection_item');

    Route::get('item_card/{item_id}/{action}', [StickerPrintingController::class, 'itemCard'])
        ->name('kiosk.item_card')
        ->where('action', 'repack|replace|defect');

    Route::post('item_card/{item_id}/defect', [StickerPrintingController::class, 'processDefect'])
        ->name('kiosk.process_defect');

    Route::post('item_card/{orderItem}/repack', [StickerPrintingController::class, 'processRepack'])
        ->name('kiosk.process_repack');

    Route::post('item_card/{orderItem}/replace', [StickerPrintingController::class, 'processReplace'])
        ->name('kiosk.process_replace');
});
