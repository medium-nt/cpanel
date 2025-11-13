<?php

use App\Models\MovementMaterial;

Route::prefix('/movements_by_marketplace_order')->group(function () {
    Route::get('', [App\Http\Controllers\MovementMaterialByMarketplaceOrderController::class, 'index'])
        ->can('viewAny', MovementMaterial::class)
        ->name('movements_by_marketplace_order.index');
});
