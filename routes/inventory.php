<?php

use App\Models\MovementMaterial;

Route::prefix('/inventory')->group(function () {
    Route::get('/warehouse', [App\Http\Controllers\InventoryController::class, 'byWarehouse'])
        ->can('viewAny', MovementMaterial::class)
        ->name('inventory.warehouse');

    Route::get('/workshop', [App\Http\Controllers\InventoryController::class, 'byWorkshop'])
        ->can('viewAny', MovementMaterial::class)
        ->name('inventory.workshop');
});
