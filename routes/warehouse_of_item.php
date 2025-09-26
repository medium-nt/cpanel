<?php

use App\Models\Shelf;
use App\Models\User;

Route::prefix('/warehouse_of_item')->group(function () {
    Route::get('', [App\Http\Controllers\WarehouseOfItemController::class, 'index'])
        ->can('viewAny', Shelf::class)
        ->name('warehouse_of_item.index');

    Route::get('/new_refunds', [App\Http\Controllers\WarehouseOfItemController::class, 'newRefunds'])
        ->can('create', Shelf::class)
        ->name('warehouse_of_item.new_refunds');

    Route::get('/storage_barcode/{marketplace_item}', [App\Http\Controllers\WarehouseOfItemController::class, 'getStorageBarcodeFile'])
        ->name('warehouse_of_item.storage_barcode');

    Route::post('/save_storage/{marketplace_item}', [App\Http\Controllers\WarehouseOfItemController::class, 'saveStorage'])
        ->name('warehouse_of_item.save_storage');
});
