<?php

use App\Http\Controllers\DefectMaterialController;
use App\Models\MovementMaterial;

Route::prefix('/defect_materials')->group(function () {
    Route::get('', [DefectMaterialController::class, 'index'])
        ->can('viewAny', MovementMaterial::class)
        ->name('defect_materials.index');

    Route::get('/create', [DefectMaterialController::class, 'create'])
        ->can('create', \App\Models\Order::class)
        ->name('defect_materials.create');

    Route::post('/store', [DefectMaterialController::class, 'store'])
        ->can('create', MovementMaterial::class)
        ->name('defect_materials.store');

    Route::get('/{order}/approve_reject', [DefectMaterialController::class, 'approve_reject'])
        ->can('approve_reject', 'order')
        ->name('defect_materials.approve_reject');

    Route::get('/{order}/save', [DefectMaterialController::class, 'save'])
        ->can('update', 'order')
        ->name('defect_materials.save');

    Route::get('/{order}/pick_up', [DefectMaterialController::class, 'pick_up'])
        ->can('pick_up', 'order')
        ->name('defect_materials.pick_up');

    Route::get('/scan', [DefectMaterialController::class, 'scan'])
        ->can('viewAny', MovementMaterial::class)
        ->name('defect_materials.scan');

    Route::delete('/{order}/delete', [DefectMaterialController::class, 'delete'])
        ->can('delete', 'order')
        ->name('defect_materials.delete');
});
