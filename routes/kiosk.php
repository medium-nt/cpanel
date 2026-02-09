<?php

use App\Http\Controllers\StickerPrintingController;

Route::prefix('/kiosk')->group(function () {
    Route::get('', [StickerPrintingController::class, 'kiosk'])
        ->name('kiosk');
    Route::get('opening_closing_shifts', [StickerPrintingController::class, 'opening_closing_shifts'])
        ->name('opening_closing_shifts');
    Route::get('statistics_reports', [StickerPrintingController::class, 'statisticsReports'])
        ->name('statistics_reports');

    Route::get('defects', [StickerPrintingController::class, 'defects'])
        ->name('defects.create');
    Route::post('defects', [StickerPrintingController::class, 'saveDefects'])
        ->name('defects.store');
    Route::get('defects/print_sticker/{order}', [StickerPrintingController::class, 'printSticker'])
        ->name('defects.print_sticker');

    Route::get('product-label/{marketplaceOrderItem}', [StickerPrintingController::class, 'printProductLabel'])
        ->name('kiosk.product-label');

    Route::get('api/roll/{roll_code}', [StickerPrintingController::class, 'getRollByCode'])
        ->name('kiosk.api.roll');
});
