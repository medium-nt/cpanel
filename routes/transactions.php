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

    Route::get('/payout_salary/', [App\Http\Controllers\TransactionController::class, 'createPayoutSalary'])
        ->can('create', Transaction::class)
        ->name('transactions.payout_salary');

    Route::post('/store_payout_salary', [App\Http\Controllers\TransactionController::class, 'storePayoutSalary'])
        ->can('create', Transaction::class)
        ->name('transactions.store_payout_salary');

    Route::get('/payout_bonus/', [App\Http\Controllers\TransactionController::class, 'createPayoutBonus'])
        ->can('create', Transaction::class)
        ->name('transactions.payout_bonus');


    Route::post('/store_payout_bonus', [App\Http\Controllers\TransactionController::class, 'storePayoutBonus'])
        ->can('create', Transaction::class)
        ->name('transactions.store_payout_bonus');
});
