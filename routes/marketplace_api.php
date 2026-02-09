<?php

use App\Http\Controllers\MarketplaceApiController;

Route::prefix('/marketplace_api')->group(function () {
    Route::get('check_skuz', [MarketplaceApiController::class, 'checkSkuz'])
//        ->can('viewAny', User::class)
        ->name('marketplace_api.checkSkuz');

    Route::get('new_order', [MarketplaceApiController::class, 'uploadingNewProducts'])
//        ->can('viewAny', User::class)
        ->name('marketplace_api.newOrder');

    Route::get('check_duplicate_skuz', [MarketplaceApiController::class, 'checkDuplicateSkuz'])
//        ->can('viewAny', User::class)
        ->name('marketplace_api.checkDuplicateSkuz');

    Route::get('check_cancelled', [MarketplaceApiController::class, 'uploadingCancelledProducts'])
//        ->can('viewAny', User::class)
        ->name('marketplace_api.check_cancelled');

});
