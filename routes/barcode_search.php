<?php

use App\Http\Controllers\BarcodeSearchController;

Route::get('/barcode_search', [BarcodeSearchController::class, 'index'])
    ->name('barcode_search.index');
