<?php

use App\Http\Controllers\OzonReturnsController;
use Illuminate\Support\Facades\Route;

Route::prefix('/ozon-returns')->group(function () {
    Route::get('/', [OzonReturnsController::class, 'index'])
        ->name('ozon_returns.index');

    Route::get('/products', [OzonReturnsController::class, 'products'])
        ->name('ozon_returns.products');

    Route::post('/refresh-barcode', [OzonReturnsController::class, 'refreshBarcode'])
        ->name('ozon_returns.refresh_barcode');

    Route::post('/giveout-info', [OzonReturnsController::class, 'giveoutInfo'])
        ->name('ozon_returns.giveout_info');
});
