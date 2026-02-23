<?php

use App\Http\Controllers\MaterialMovementController;
use App\Models\MovementMaterial;

Route::prefix('/material-movements')->group(function () {
    Route::get('', [MaterialMovementController::class, 'index'])
        ->can('viewAny', MovementMaterial::class)
        ->name('material-movements.index');
});
