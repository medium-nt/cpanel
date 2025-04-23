<?php

use App\Models\MovementMaterial;
use App\Models\Order;

Route::prefix('/movements_by_marketplace_order')->group(function () {
    Route::get('', [App\Http\Controllers\MovementMaterialByMarketplaceOrderController::class, 'index'])
        ->can('viewAny', MovementMaterial::class)
        ->name('movements_to_workshop.index');
});
