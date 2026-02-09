<?php

use App\Http\Controllers\MovementMaterialByMarketplaceOrderController;
use App\Models\MovementMaterial;

Route::prefix('/movements_by_marketplace_order')->group(function () {
    Route::get('', [MovementMaterialByMarketplaceOrderController::class, 'index'])
        ->can('viewAny', MovementMaterial::class)
        ->name('movements_by_marketplace_order.index');
});
