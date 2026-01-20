<?php

Route::prefix('/kiosk')->group(function () {
    Route::get('', [App\Http\Controllers\StickerPrintingController::class, 'test'])
        ->name('kiosk');
});
