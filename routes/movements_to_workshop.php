<?php

use App\Models\MovementMaterial;
use App\Models\Order;

Route::prefix('/movements_to_workshop')->group(function () {
    Route::get('', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'index'])
        ->can('viewAny', MovementMaterial::class)
        ->name('movements_to_workshop.index');

    Route::get('/create', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'create'])
        ->can('create', MovementMaterial::class)
        ->name('movements_to_workshop.create');

    Route::post('/store', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'store'])
        ->can('create', MovementMaterial::class)
        ->name('movements_to_workshop.store');

    Route::get('/{order}/collect', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'collect'])
        ->can('collect', 'order')
        ->name('movements_to_workshop.collect');

    Route::put('/save_collect/{order}', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'save_collect'])
        ->can('collect', 'order')
        ->name('movements_to_workshop.save_collect');

    Route::get('/{order}/receive', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'receive'])
        ->can('update', MovementMaterial::class)
        ->name('movements_to_workshop.receive');

    Route::put('/save_receive/{order}', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'save_receive'])
        ->can('update', 'order')
        ->name('movements_to_workshop.save_receive');

    Route::get('/write_off', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'write_off'])
        ->can('write_off', Order::class)
        ->name('movements_to_workshop.write_off');

    Route::post('/save_write_off', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'save_write_off'])
        ->can('write_off', Order::class)
        ->name('movements_to_workshop.save_write_off');

    Route::get('/{order}/delete', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'delete'])
        ->can('delete', 'order')
        ->name('movements_to_workshop.destroy');
});
