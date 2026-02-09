<?php

use App\Http\Controllers\TransactionController;
use App\Models\Transaction;

Route::prefix('/salary')->group(function () {
    Route::get('', [TransactionController::class, 'getSalaryTable'])
        ->can('viewAny', Transaction::class)
        ->name('transactions.salary');

});
