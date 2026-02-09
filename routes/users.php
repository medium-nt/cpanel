<?php

use App\Http\Controllers\UsersController;
use App\Models\User;

Route::prefix('/users')->group(function () {
    Route::get('', [UsersController::class, 'index'])
        ->can('viewAny', User::class)
        ->name('users.index');

    Route::get('/create', [UsersController::class, 'create'])
        ->can('create', User::class)
        ->name('users.create');

    Route::post('/store', [UsersController::class, 'store'])
        ->can('create', User::class)
        ->name('users.store');

    Route::get('/{user}/edit', [UsersController::class, 'edit'])
        ->can('update', 'user')
        ->name('users.edit');

    Route::put('/update/{user}', [UsersController::class, 'update'])
        ->can('update', 'user')
        ->name('users.update');

    Route::put('/motivation_update/{user}', [UsersController::class, 'motivationUpdate'])
        ->can('update', 'user')
        ->name('users.motivation_update');

    Route::put('/rate_update/{user}', [UsersController::class, 'rateUpdate'])
        ->can('update', 'user')
        ->name('users.rate_update');

    Route::delete('/delete/{user}', [UsersController::class, 'destroy'])
        ->can('delete', 'user')
        ->name('users.destroy');

    Route::get('/{user}/get_barcode', [UsersController::class, 'getBarcode'])
        ->name('users.get_barcode');
});
