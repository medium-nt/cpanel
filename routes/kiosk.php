<?php

Route::prefix('/kiosk')->group(function () {
    Route::get('', [App\Http\Controllers\StickerPrintingController::class, 'test'])
        ->name('kiosk');
    Route::get('opening_closing_shifts', [App\Http\Controllers\StickerPrintingController::class, 'opening_closing_shifts'])
        ->name('opening_closing_shifts');
    Route::get('statistics_reports', [App\Http\Controllers\StickerPrintingController::class, 'statisticsReports'])
        ->name('statistics_reports');

    Route::get('defects', [App\Http\Controllers\StickerPrintingController::class, 'defects'])
        ->name('defects');
    Route::post('defects', [App\Http\Controllers\StickerPrintingController::class, 'saveDefects'])
        ->name('defects');
});
