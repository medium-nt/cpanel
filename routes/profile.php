<?php

use App\Http\Controllers\UsersController;

Route::prefix('/profile')->group(function () {
    Route::get('', [UsersController::class, 'profile'])
        ->name('profile');
    Route::put('', [UsersController::class, 'profileUpdate'])
        ->name('profile.update');
    Route::get('disconnectTg', [UsersController::class, 'disconnectTg'])
        ->name('profile.disconnectTg');
});
