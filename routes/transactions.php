<?php

use App\Models\Transaction;

Route::prefix('/transactions')->group(function () {
    Route::get('', [App\Http\Controllers\TransactionController::class, 'index'])
        ->can('viewAny', Transaction::class)
        ->name('transactions.index');

    Route::get('/create/{type}', [App\Http\Controllers\TransactionController::class, 'create'])
        ->can('create', Transaction::class)
        ->name('transactions.create');

    Route::post('/store', [App\Http\Controllers\TransactionController::class, 'store'])
        ->can('create', Transaction::class)
        ->name('transactions.store');

    Route::get('/{transaction}/edit', [App\Http\Controllers\TransactionController::class, 'edit'])
        ->can('update', 'transaction')
        ->name('transactions.edit');

    Route::put('/update/{transaction}', [App\Http\Controllers\TransactionController::class, 'update'])
        ->can('update', 'transaction')
        ->name('transactions.update');

    Route::delete('/delete/{transaction}', [App\Http\Controllers\TransactionController::class, 'destroy'])
        ->can('delete', 'transaction')
        ->name('transactions.destroy');

    Route::get('/payout/', [App\Http\Controllers\TransactionController::class, 'createPayout'])
        ->can('create', Transaction::class)
        ->name('transactions.payout');

    Route::post('/store_payout', [App\Http\Controllers\TransactionController::class, 'storePayout'])
        ->can('create', Transaction::class)
        ->name('transactions.store_payout');
});
