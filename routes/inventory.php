<?php

use App\Models\InventoryCheck;
use App\Models\MovementMaterial;

Route::prefix('/inventory')->group(function () {
    Route::get('/warehouse', [App\Http\Controllers\InventoryController::class, 'byWarehouse'])
        ->can('viewAny', MovementMaterial::class)
        ->name('inventory.warehouse');

    Route::get('/workshop', [App\Http\Controllers\InventoryController::class, 'byWorkshop'])
        ->can('viewAny', MovementMaterial::class)
        ->name('inventory.workshop');

    Route::get('/inventory_checks', [App\Http\Controllers\InventoryController::class, 'inventoryChecks'])
        ->can('viewAny', InventoryCheck::class)
        ->name('inventory.inventory_checks');

    Route::get('/{inventory}/show', [App\Http\Controllers\InventoryController::class, 'show'])
        ->can('view', InventoryCheck::class)
        ->name('inventory.show');

    Route::get('/create', [App\Http\Controllers\InventoryController::class, 'create'])
        ->can('create', InventoryCheck::class)
        ->name('inventory.create');

    Route::post('/store', [App\Http\Controllers\InventoryController::class, 'store'])
        ->can('create', InventoryCheck::class)
        ->name('inventory.store');

    Route::delete('/delete/{inventory}', [App\Http\Controllers\InventoryController::class, 'destroy'])
        ->can('delete', InventoryCheck::class)
        ->name('inventory.destroy');

});
