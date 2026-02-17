<?php

use App\Http\Controllers\WarehouseOfItemController;
use App\Models\Shelf;

Route::prefix('/warehouse_of_item')->group(function () {
    Route::get('', [WarehouseOfItemController::class, 'index'])
        ->can('viewAny', Shelf::class)
        ->name('warehouse_of_item.index');

    Route::get('/new_refunds', [WarehouseOfItemController::class, 'newRefunds'])
        ->can('create', Shelf::class)
        ->name('warehouse_of_item.new_refunds');

    Route::get('/add_group', [WarehouseOfItemController::class, 'addGroup'])
        ->can('createAdmin', Shelf::class)
        ->name('warehouse_of_item.add_group');

    Route::get('/save_group', [WarehouseOfItemController::class, 'saveGroup'])
        ->can('createAdmin', Shelf::class)
        ->name('warehouse_of_item.save_group');

    Route::get('/storage_barcode', [WarehouseOfItemController::class, 'getStorageBarcodeFile'])
        ->name('warehouse_of_item.storage_barcode');

    Route::post('/save_storage/{marketplace_item}', [WarehouseOfItemController::class, 'saveStorage'])
        ->can('create', Shelf::class)
        ->name('warehouse_of_item.save_storage');

    Route::get('/to_pick_list', [WarehouseOfItemController::class, 'toPickList'])
        ->can('viewAny', Shelf::class)
        ->name('warehouse_of_item.to_pick_list');

    Route::get('/to_pick_list_print', [WarehouseOfItemController::class, 'toPickListPrint'])
        ->can('viewAny', Shelf::class)
        ->name('warehouse_of_item.to_pick_list_print');

    Route::get('/to_pick/{order}', [WarehouseOfItemController::class, 'toPick'])
        ->can('create', Shelf::class)
        ->name('warehouse_of_item.to_pick');

    Route::put('/labeling/{marketplace_order}/{marketplace_order_item}', [WarehouseOfItemController::class, 'labeling'])
        ->can('update', Shelf::class)
        ->name('warehouse_of_item.labeling');

    Route::get('/done/{marketplace_order}', [WarehouseOfItemController::class, 'done'])
        ->can('update', Shelf::class)
        ->name('warehouse_of_item.done');

    Route::get('/to_work/{marketplace_order}', [WarehouseOfItemController::class, 'toWork'])
        ->can('update', Shelf::class)
        ->name('warehouse_of_item.to_work');

    Route::get('/shelf_change', [WarehouseOfItemController::class, 'shelfChange'])
        ->can('update', Shelf::class)
        ->name('warehouse_of_item.shelf_change');

});
