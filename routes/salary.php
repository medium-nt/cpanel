<?php

use App\Models\Transaction;

Route::prefix('/salary')->group(function () {
    Route::get('', [App\Http\Controllers\TransactionController::class, 'getSalaryTable'])
        ->can('viewAny', Transaction::class)
        ->name('transactions.salary');

});
