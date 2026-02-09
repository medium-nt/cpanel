<?php

use App\Http\Controllers\MovementDefectMaterialToSupplierController;
use App\Models\MovementMaterial;

Route::prefix('/movements_defect_to_supplier')->group(function () {
    Route::get('', [MovementDefectMaterialToSupplierController::class, 'index'])
        ->can('viewAny_defect', MovementMaterial::class)
        ->name('movements_defect_to_supplier.index');

    Route::get('/create', [MovementDefectMaterialToSupplierController::class, 'create'])
        ->can('create_defect', MovementMaterial::class)
        ->name('movements_defect_to_supplier.create');

    Route::post('/store', [MovementDefectMaterialToSupplierController::class, 'store'])
        ->can('create_defect', MovementMaterial::class)
        ->name('movements_defect_to_supplier.store');

});
