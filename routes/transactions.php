<?php

use App\Http\Controllers\TransactionController;
use App\Models\Transaction;

Route::prefix('/transactions')->group(function () {
    Route::get('', [TransactionController::class, 'index'])
        ->can('viewAny', Transaction::class)
        ->name('transactions.index');

    Route::get('/create/{type}', [TransactionController::class, 'create'])
        ->can('create', Transaction::class)
        ->name('transactions.create');

    Route::post('/store', [TransactionController::class, 'store'])
        ->can('create', Transaction::class)
        ->name('transactions.store');

    Route::delete('/delete/{transaction}', [TransactionController::class, 'destroy'])
        ->can('delete', 'transaction')
        ->name('transactions.destroy');

    Route::get('/payout_salary/', [TransactionController::class, 'createPayoutSalary'])
        ->can('create', Transaction::class)
        ->name('transactions.payout_salary');

    Route::post('/store_payout_salary', [TransactionController::class, 'storePayoutSalary'])
        ->can('create', Transaction::class)
        ->name('transactions.store_payout_salary');

    Route::get('/payout_bonus/', [TransactionController::class, 'createPayoutBonus'])
        ->can('create', Transaction::class)
        ->name('transactions.payout_bonus');

    Route::post('/store_payout_bonus', [TransactionController::class, 'storePayoutBonus'])
        ->can('create', Transaction::class)
        ->name('transactions.store_payout_bonus');
});
