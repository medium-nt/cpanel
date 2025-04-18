<?php

use App\Models\Transaction;

Route::prefix('/transactions')->group(function () {
    Route::get('', [App\Http\Controllers\TransactionController::class, 'index'])
        ->can('viewAny', Transaction::class)
        ->name('transactions.index');

    Route::get('/create', [App\Http\Controllers\TransactionController::class, 'create'])
        ->can('create', Transaction::class)
        ->name('transactions.create');

    Route::post('/store', [App\Http\Controllers\TransactionController::class, 'store'])
        ->can('create', Transaction::class)
        ->name('transactions.store');

    Route::get('/{transaction}/edit', [App\Http\Controllers\TransactionController::class, 'edit'])
        ->can('update', 'transactions')
        ->name('transactions.edit');

    Route::put('/update/{transaction}', [App\Http\Controllers\TransactionController::class, 'update'])
        ->can('update', 'transactions')
        ->name('transactions.update');

    Route::delete('/delete/{transaction}', [App\Http\Controllers\TransactionController::class, 'destroy'])
        ->can('delete', 'transactions')
        ->name('transactions.destroy');
});
