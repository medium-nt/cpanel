<?php

use App\Models\Shelf;

Route::prefix('/shelves')->group(function () {
    Route::get('', [App\Http\Controllers\ShelfController::class, 'index'])
        ->can('viewAny', Shelf::class)
        ->name('shelves.index');

    Route::get('/create', [App\Http\Controllers\ShelfController::class, 'create'])
        ->can('create', Shelf::class)
        ->name('shelves.create');

    Route::post('/store', [App\Http\Controllers\ShelfController::class, 'store'])
        ->can('create', Shelf::class)
        ->name('shelves.store');

    Route::get('/{shelf}/edit', [App\Http\Controllers\ShelfController::class, 'edit'])
        ->can('update', 'shelf')
        ->name('shelves.edit');

    Route::put('/update/{shelf}', [App\Http\Controllers\ShelfController::class, 'update'])
        ->can('update', 'shelf')
        ->name('shelves.update');

    Route::delete('/delete/{shelf}', [App\Http\Controllers\ShelfController::class, 'destroy'])
        ->can('delete', 'shelf')
        ->name('shelves.destroy');
});
