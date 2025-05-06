<?php

use App\Models\MovementMaterial;

Route::prefix('/movements_defect_to_supplier')->group(function () {
    Route::get('', [App\Http\Controllers\MovementDefectMaterialToSupplierController::class, 'index'])
        ->can('viewAny_defect', MovementMaterial::class)
        ->name('defect_materials_in_stock.index');

    Route::get('/create', [App\Http\Controllers\MovementDefectMaterialToSupplierController::class, 'create'])
        ->can('create_defect', MovementMaterial::class)
        ->name('defect_materials_in_stock.create');

    Route::post('/store', [App\Http\Controllers\MovementDefectMaterialToSupplierController::class, 'store'])
        ->can('create_defect', MovementMaterial::class)
        ->name('defect_materials_in_stock.store');

});
