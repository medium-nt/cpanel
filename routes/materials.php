<?php

use App\Models\Material;

Route::prefix('/materials')->group(function () {
    Route::get('', [App\Http\Controllers\MaterialController::class, 'index'])
        ->can('viewAny', Material::class)
        ->name('materials.index');

    Route::get('/create', [App\Http\Controllers\MaterialController::class, 'create'])
        ->can('create', Material::class)
        ->name('materials.create');

    Route::post('/store', [App\Http\Controllers\MaterialController::class, 'store'])
        ->can('create', Material::class)
        ->name('materials.store');

    Route::get('/{material}/edit', [App\Http\Controllers\MaterialController::class, 'edit'])
        ->can('update', 'material')
        ->name('materials.edit');

    Route::put('/update/{material}', [App\Http\Controllers\MaterialController::class, 'update'])
        ->can('update', 'material')
        ->name('materials.update');

    Route::delete('/delete/{material}', [App\Http\Controllers\MaterialController::class, 'destroy'])
        ->can('delete', 'material')
        ->name('materials.destroy');
});
