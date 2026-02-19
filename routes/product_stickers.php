<?php

use App\Http\Controllers\ProductStickerController;
use App\Models\ProductSticker;

Route::prefix('/product_stickers')->group(function () {
    Route::get('', [ProductStickerController::class, 'index'])
        ->can('viewAny', ProductSticker::class)
        ->name('product_stickers.index');

    Route::get('/create', [ProductStickerController::class, 'create'])
        ->can('create', ProductSticker::class)
        ->name('product_stickers.create');

    Route::post('/store', [ProductStickerController::class, 'store'])
        ->can('create', ProductSticker::class)
        ->name('product_stickers.store');

    Route::get('/{productSticker}/edit', [ProductStickerController::class, 'edit'])
        ->can('update', 'productSticker')
        ->name('product_stickers.edit');

    Route::put('/{productSticker}', [ProductStickerController::class, 'update'])
        ->can('update', 'productSticker')
        ->name('product_stickers.update');

    Route::delete('/{productSticker}', [ProductStickerController::class, 'destroy'])
        ->can('delete', 'productSticker')
        ->name('product_stickers.destroy');
});
