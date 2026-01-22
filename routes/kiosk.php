<?php

Route::prefix('/kiosk')->group(function () {
    Route::get('', [App\Http\Controllers\StickerPrintingController::class, 'test'])
        ->name('kiosk');
    Route::get('opening_closing_shifts', [App\Http\Controllers\StickerPrintingController::class, 'opening_closing_shifts'])
        ->name('opening_closing_shifts');
});
