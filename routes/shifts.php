<?php

use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('shifts')->group(function () {
    Route::get('/', [ShiftController::class, 'index'])->name('shifts.index');
    Route::get('/create', [ShiftController::class, 'create'])->name('shifts.create');
    Route::post('/', [ShiftController::class, 'store'])->name('shifts.store');
    Route::get('/search-users', [ShiftController::class, 'searchUsers'])->name('shifts.search-users');
    Route::get('/{shift}', [ShiftController::class, 'show'])->name('shifts.show');
    Route::put('/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
    Route::delete('/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');

    Route::post('/{shift}/users', [ShiftController::class, 'attachUser'])->name('shifts.users.attach');
    Route::delete('/{shift}/users/{user}', [ShiftController::class, 'detachUser'])->name('shifts.users.detach');
    Route::post('/{shift}/users/{user}/transfer', [ShiftController::class, 'transferUser'])->name('shifts.users.transfer');
    Route::delete('/{shift}/records/{recordId}', [ShiftController::class, 'destroyRecord'])->name('shifts.records.destroy');
});

Route::prefix('shift-schedule')->group(function () {
    Route::get('/', [ShiftController::class, 'scheduleIndex'])->name('shift-schedule.index');
    Route::post('/', [ShiftController::class, 'scheduleStore'])->name('shift-schedule.store');
});
