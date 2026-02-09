<?php

Route::prefix('/kiosk')->group(function () {
    Route::get('', [App\Http\Controllers\StickerPrintingController::class, 'kiosk'])
        ->name('kiosk');
    Route::get('opening_closing_shifts', [App\Http\Controllers\StickerPrintingController::class, 'opening_closing_shifts'])
        ->name('opening_closing_shifts');
    Route::get('statistics_reports', [App\Http\Controllers\StickerPrintingController::class, 'statisticsReports'])
        ->name('statistics_reports');

    Route::get('defects', [App\Http\Controllers\StickerPrintingController::class, 'defects'])
        ->name('defects.create');
    Route::post('defects', [App\Http\Controllers\StickerPrintingController::class, 'saveDefects'])
        ->name('defects.store');
    Route::get('defects/print_sticker/{order}', [App\Http\Controllers\StickerPrintingController::class, 'printSticker'])
        ->name('defects.print_sticker');

    Route::get('product-label/{marketplaceOrderItem}', [App\Http\Controllers\StickerPrintingController::class, 'printProductLabel'])
        ->name('kiosk.product-label');

    Route::get('api/roll/{roll_code}', [App\Http\Controllers\StickerPrintingController::class, 'getRollByCode'])
        ->name('kiosk.api.roll');
});
