<?php

Route::prefix('/rolls')->group(function () {
    Route::get('', [App\Http\Controllers\RollController::class, 'index'])
        ->name('rolls.index');
    Route::get('/show/{roll}', [App\Http\Controllers\RollController::class, 'show'])
        ->name('rolls.show');
    Route::get('/print/roll/{roll}', [App\Http\Controllers\RollController::class, 'printRoll'])
        ->name('rolls.printRoll');
    Route::get('/print/order/{order}', [App\Http\Controllers\RollController::class, 'printOrder'])
        ->name('rolls.printOrder');
});
