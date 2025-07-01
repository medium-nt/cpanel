<?php

use App\Models\MovementMaterial;

Route::prefix('/write_off_remnants')->group(function () {
    Route::get('', [App\Http\Controllers\WriteOffRemnantsController::class, 'index'])
        ->can('viewAny_remnants', MovementMaterial::class)
        ->name('write_off_remnants.index');

    Route::get('/create', [App\Http\Controllers\WriteOffRemnantsController::class, 'create'])
        ->can('create_remnants', MovementMaterial::class)
        ->name('write_off_remnants.create');

    Route::post('/store', [App\Http\Controllers\WriteOffRemnantsController::class, 'store'])
        ->can('create_remnants', MovementMaterial::class)
        ->name('write_off_remnants.store');

});
