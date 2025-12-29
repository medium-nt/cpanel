<?php

Route::prefix('/rolls')->group(function () {
    Route::get('', [App\Http\Controllers\RollController::class, 'index'])
        ->can('viewAny', App\Models\Roll::class)
        ->name('rolls.index');
    Route::get('/show/{roll}', [App\Http\Controllers\RollController::class, 'show'])
        ->can('view', App\Models\Roll::class)
        ->name('rolls.show');
    Route::get('/print/roll/{roll}', [App\Http\Controllers\RollController::class, 'printRoll'])
        ->can('print', 'roll')
        ->name('rolls.printRoll');
    Route::get('/print/order/{order}', [App\Http\Controllers\RollController::class, 'printOrder'])
        ->can('print', App\Models\Roll::class)
        ->name('rolls.printOrder');
    Route::delete('/delete/{roll}', [App\Http\Controllers\RollController::class, 'destroy'])
        ->can('delete', 'roll')
        ->name('rolls.destroy');
});
