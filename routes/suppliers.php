<?php

use App\Http\Controllers\SupplierController;
use App\Models\Supplier;

Route::prefix('/suppliers')->group(function () {
    Route::get('', [SupplierController::class, 'index'])
        ->can('viewAny', Supplier::class)
        ->name('suppliers.index');

    Route::get('/create', [SupplierController::class, 'create'])
        ->can('create', Supplier::class)
        ->name('suppliers.create');

    Route::post('/store', [SupplierController::class, 'store'])
        ->can('create', Supplier::class)
        ->name('suppliers.store');

    Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])
        ->can('update', 'supplier')
        ->name('suppliers.edit');

    Route::put('/update/{supplier}', [SupplierController::class, 'update'])
        ->can('update', 'supplier')
        ->name('suppliers.update');

    Route::delete('/delete/{supplier}', [SupplierController::class, 'destroy'])
        ->can('delete', 'supplier')
        ->name('suppliers.destroy');
});
