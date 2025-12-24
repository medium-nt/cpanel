<?php

use App\Models\MovementMaterial;

Route::prefix('/movements_from_supplier')->group(function () {
    Route::get('', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'index'])
        ->can('viewAny', MovementMaterial::class)
        ->name('movements_from_supplier.index');

    Route::get('/create', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'create'])
        ->can('create', MovementMaterial::class)
        ->name('movements_from_supplier.create');

    Route::post('/store', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'store'])
        ->can('create', MovementMaterial::class)
        ->name('movements_from_supplier.store');

    Route::get('/{order}/edit', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'edit'])
        ->can('update', 'order')
        ->name('movements_from_supplier.edit');

    Route::put('/update/{order}', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'update'])
        ->name('movements_from_supplier.update');

    Route::delete('/delete/{order}', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'destroy'])
        ->can('delete', 'order')
        ->name('movements_from_supplier.destroy');
});
