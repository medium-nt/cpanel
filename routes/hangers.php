<?php

use App\Http\Controllers\HangerController;
use App\Models\Hanger;

Route::prefix('/hangers')->group(function () {
    Route::get('', [HangerController::class, 'index'])
        ->can('viewAny', Hanger::class)
        ->name('hangers.index');

    Route::get('/create', [HangerController::class, 'create'])
        ->can('create', Hanger::class)
        ->name('hangers.create');

    Route::post('/store', [HangerController::class, 'store'])
        ->can('create', Hanger::class)
        ->name('hangers.store');

    Route::get('/{hanger}/edit', [HangerController::class, 'edit'])
        ->can('update', 'hanger')
        ->name('hangers.edit');

    Route::put('/update/{hanger}', [HangerController::class, 'update'])
        ->can('update', 'hanger')
        ->name('hangers.update');

    Route::delete('/delete/{hanger}', [HangerController::class, 'destroy'])
        ->can('delete', 'hanger')
        ->name('hangers.destroy');
});
