<?php

use App\Http\Controllers\InventoryController;
use App\Models\InventoryCheck;
use App\Models\MovementMaterial;

Route::prefix('/inventory')->group(function () {
    Route::get('/warehouse', [InventoryController::class, 'byWarehouse'])
        ->can('viewAny', MovementMaterial::class)
        ->name('inventory.warehouse');

    Route::get('/workshop', [InventoryController::class, 'byWorkshop'])
        ->can('viewAny', MovementMaterial::class)
        ->name('inventory.workshop');

    Route::get('/inventory_checks', [InventoryController::class, 'inventoryChecks'])
        ->can('viewAny', InventoryCheck::class)
        ->name('inventory.inventory_checks');

    Route::get('/{inventory}/show', [InventoryController::class, 'show'])
        ->can('view', InventoryCheck::class)
        ->name('inventory.show');

    Route::get('/create', [InventoryController::class, 'create'])
        ->can('create', InventoryCheck::class)
        ->name('inventory.create');

    Route::post('/store', [InventoryController::class, 'store'])
        ->can('create', InventoryCheck::class)
        ->name('inventory.store');

    Route::delete('/delete/{inventory}', [InventoryController::class, 'destroy'])
        ->can('delete', InventoryCheck::class)
        ->name('inventory.destroy');

});
