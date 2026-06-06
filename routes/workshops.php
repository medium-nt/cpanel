<?php

use App\Http\Controllers\WorkshopController;
use Illuminate\Support\Facades\Route;

Route::prefix('workshops')->group(function () {
    Route::get('/', [WorkshopController::class, 'index'])->name('workshops.index');
    Route::get('/create', [WorkshopController::class, 'create'])->name('workshops.create');
    Route::post('/', [WorkshopController::class, 'store'])->name('workshops.store');
    Route::get('/{workshop}/edit', [WorkshopController::class, 'edit'])->name('workshops.edit');
    Route::put('/{workshop}', [WorkshopController::class, 'update'])->name('workshops.update');
    Route::delete('/{workshop}', [WorkshopController::class, 'destroy'])->name('workshops.destroy');
});
