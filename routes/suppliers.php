<?php

use App\Models\Supplier;

Route::prefix('/suppliers')->group(function () {
    Route::get('', [App\Http\Controllers\SupplierController::class, 'index'])
        ->can('viewAny', Supplier::class)
        ->name('suppliers.index');

    Route::get('/create', [App\Http\Controllers\SupplierController::class, 'create'])
        ->can('create', Supplier::class)
        ->name('suppliers.create');

    Route::post('/store', [App\Http\Controllers\SupplierController::class, 'store'])
        ->can('create', Supplier::class)
        ->name('suppliers.store');

    Route::get('/{supplier}/edit', [App\Http\Controllers\SupplierController::class, 'edit'])
        ->can('update', 'supplier')
        ->name('suppliers.edit');

    Route::put('/update/{supplier}', [App\Http\Controllers\SupplierController::class, 'update'])
        ->can('update', 'supplier')
        ->name('suppliers.update');

    Route::delete('/delete/{supplier}', [App\Http\Controllers\SupplierController::class, 'destroy'])
        ->can('delete', 'supplier')
        ->name('suppliers.destroy');
});
