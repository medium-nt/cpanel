<?php

use App\Http\Controllers\WriteOffRemnantsController;
use App\Models\MovementMaterial;

Route::prefix('/write_off_remnants')->group(function () {
    Route::get('', [WriteOffRemnantsController::class, 'index'])
        ->can('viewAny_remnants', MovementMaterial::class)
        ->name('write_off_remnants.index');

    Route::get('/create', [WriteOffRemnantsController::class, 'create'])
        ->can('create_remnants', MovementMaterial::class)
        ->name('write_off_remnants.create');

    Route::post('/store', [WriteOffRemnantsController::class, 'store'])
        ->can('create_remnants', MovementMaterial::class)
        ->name('write_off_remnants.store');

});
