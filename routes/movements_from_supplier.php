<?php

use App\Http\Controllers\MovementMaterialFromSupplierController;
use App\Models\MovementMaterial;

Route::prefix('/movements_from_supplier')->group(function () {
    Route::get('', [MovementMaterialFromSupplierController::class, 'index'])
        ->can('viewAny', MovementMaterial::class)
        ->name('movements_from_supplier.index');

    Route::get('/create', [MovementMaterialFromSupplierController::class, 'create'])
        ->can('create', MovementMaterial::class)
        ->name('movements_from_supplier.create');

    Route::post('/store', [MovementMaterialFromSupplierController::class, 'store'])
        ->can('create', MovementMaterial::class)
        ->name('movements_from_supplier.store');

    Route::get('/{order}/edit', [MovementMaterialFromSupplierController::class, 'edit'])
        ->can('update', 'order')
        ->name('movements_from_supplier.edit');

    Route::put('/update/{order}', [MovementMaterialFromSupplierController::class, 'update'])
        ->name('movements_from_supplier.update');

    Route::delete('/delete/{order}', [MovementMaterialFromSupplierController::class, 'destroy'])
        ->can('delete', 'order')
        ->name('movements_from_supplier.destroy');
});
