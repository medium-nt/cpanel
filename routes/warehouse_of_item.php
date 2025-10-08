<?php

use App\Models\Shelf;

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
        ->can('create', Shelf::class)
        ->name('warehouse_of_item.save_storage');

    Route::get('/to_pick_list', [App\Http\Controllers\WarehouseOfItemController::class, 'toPickList'])
        ->can('viewAny', Shelf::class)
        ->name('warehouse_of_item.to_pick_list');

    Route::get('/to_pick/{order}', [App\Http\Controllers\WarehouseOfItemController::class, 'toPick'])
        ->can('create', Shelf::class)
        ->name('warehouse_of_item.to_pick');

    Route::put('/labeling/{marketplace_order}/{marketplace_order_item}', [App\Http\Controllers\WarehouseOfItemController::class, 'labeling'])
        ->can('update', Shelf::class)
        ->name('warehouse_of_item.labeling');

    Route::get('/done/{marketplace_order}', [App\Http\Controllers\WarehouseOfItemController::class, 'done'])
        ->can('update', Shelf::class)
        ->name('warehouse_of_item.done');

    Route::get('/to_work/{marketplace_order}', [App\Http\Controllers\WarehouseOfItemController::class, 'toWork'])
        ->can('update', Shelf::class)
        ->name('warehouse_of_item.to_work');

});
