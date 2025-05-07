<?php

use App\Models\MovementMaterial;

Route::prefix('/movements_defect_to_supplier')->group(function () {
    Route::get('', [App\Http\Controllers\MovementDefectMaterialToSupplierController::class, 'index'])
        ->can('viewAny_defect', MovementMaterial::class)
        ->name('movements_defect_to_supplier.index');

    Route::get('/create', [App\Http\Controllers\MovementDefectMaterialToSupplierController::class, 'create'])
        ->can('create_defect', MovementMaterial::class)
        ->name('movements_defect_to_supplier.create');

    Route::post('/store', [App\Http\Controllers\MovementDefectMaterialToSupplierController::class, 'store'])
        ->can('create_defect', MovementMaterial::class)
        ->name('movements_defect_to_supplier.store');

});
