<?php

use App\Http\Controllers\RollController;

Route::prefix('/rolls')->group(function () {
    Route::get('', [RollController::class, 'index'])
        ->can('viewAny', App\Models\Roll::class)
        ->name('rolls.index');
    Route::get('/show/{roll}', [RollController::class, 'show'])
        ->can('view', App\Models\Roll::class)
        ->name('rolls.show');
    Route::get('/print/roll/{roll}', [RollController::class, 'printRoll'])
        ->can('print', 'roll')
        ->name('rolls.printRoll');
    Route::get('/print/order/{order}', [RollController::class, 'printOrder'])
        ->can('print', App\Models\Roll::class)
        ->name('rolls.printOrder');
    Route::delete('/delete/{roll}', [RollController::class, 'destroy'])
        ->can('delete', 'roll')
        ->name('rolls.destroy');
});
