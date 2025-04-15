<?php

use App\Models\MovementMaterial;

Route::prefix('/defect_materials')->group(function () {
    Route::get('', [App\Http\Controllers\DefectMaterialController::class, 'index'])
        ->can('viewAny', MovementMaterial::class)
        ->name('defect_materials.index');

    Route::get('/create', [App\Http\Controllers\DefectMaterialController::class, 'create'])
        ->can('create', \App\Models\Order::class)
        ->name('defect_materials.create');

    Route::post('/store', [App\Http\Controllers\DefectMaterialController::class, 'store'])
        ->can('create', MovementMaterial::class)
        ->name('defect_materials.store');

    Route::get('/{order}/approve_reject', [App\Http\Controllers\DefectMaterialController::class, 'approve_reject'])
        ->can('approve_reject', 'order')
        ->name('defect_materials.approve_reject');

    Route::get('/{order}/save', [App\Http\Controllers\DefectMaterialController::class, 'save'])
        ->can('update', 'order')
        ->name('defect_materials.save');

    Route::get('/{order}/pick_up', [App\Http\Controllers\DefectMaterialController::class, 'pick_up'])
        ->can('pick_up', 'order')
        ->name('defect_materials.pick_up');
});
