<?php

use App\Models\User;

Route::prefix('/marketplace_api')->group(function () {
    Route::get('check_skuz', [App\Http\Controllers\MarketplaceApiController::class, 'checkSkuz'])
//        ->can('viewAny', User::class)
        ->name('marketplace_api.checkSkuz');

    Route::get('new_order', [App\Http\Controllers\MarketplaceApiController::class, 'uploadingNewProducts'])
//        ->can('viewAny', User::class)
        ->name('marketplace_api.newOrder');

    Route::get('check_duplicate_skuz', [App\Http\Controllers\MarketplaceApiController::class, 'checkDuplicateSkuz'])
//        ->can('viewAny', User::class)
        ->name('marketplace_api.checkDuplicateSkuz');

    Route::get('barcode', [App\Http\Controllers\MarketplaceApiController::class, 'getBarcodeFile'])
//        ->can('viewAny', User::class)
        ->name('marketplace_api.barcode');

});
