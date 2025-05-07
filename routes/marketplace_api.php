<?php

use App\Models\User;

Route::prefix('/marketplace_api')->group(function () {
    Route::get('check_skuz', [App\Http\Controllers\MarketplaceApiController::class, 'checkSkuz'])
//        ->can('viewAny', User::class)
        ->name('marketplace_api.checkSkuz');
});
