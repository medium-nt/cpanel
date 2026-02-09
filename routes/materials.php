<?php

use App\Http\Controllers\MaterialController;
use App\Models\Material;

Route::prefix('/materials')->group(function () {
    Route::get('', [MaterialController::class, 'index'])
        ->can('viewAny', Material::class)
        ->name('materials.index');

    Route::get('/create', [MaterialController::class, 'create'])
        ->can('create', Material::class)
        ->name('materials.create');

    Route::post('/store', [MaterialController::class, 'store'])
        ->can('create', Material::class)
        ->name('materials.store');

    Route::get('/{material}/edit', [MaterialController::class, 'edit'])
        ->can('update', 'material')
        ->name('materials.edit');

    Route::put('/update/{material}', [MaterialController::class, 'update'])
        ->can('update', 'material')
        ->name('materials.update');

    Route::delete('/delete/{material}', [MaterialController::class, 'destroy'])
        ->can('delete', 'material')
        ->name('materials.destroy');
});
