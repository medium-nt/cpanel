<?php

use App\Http\Controllers\ShelfController;
use App\Models\Shelf;

Route::prefix('/shelves')->group(function () {
    Route::get('', [ShelfController::class, 'index'])
        ->can('viewAny', Shelf::class)
        ->name('shelves.index');

    Route::get('/create', [ShelfController::class, 'create'])
        ->can('create', Shelf::class)
        ->name('shelves.create');

    Route::post('/store', [ShelfController::class, 'store'])
        ->can('create', Shelf::class)
        ->name('shelves.store');

    Route::get('/{shelf}/edit', [ShelfController::class, 'edit'])
        ->can('update', 'shelf')
        ->name('shelves.edit');

    Route::put('/update/{shelf}', [ShelfController::class, 'update'])
        ->can('update', 'shelf')
        ->name('shelves.update');

    Route::delete('/delete/{shelf}', [ShelfController::class, 'destroy'])
        ->can('delete', 'shelf')
        ->name('shelves.destroy');
});
