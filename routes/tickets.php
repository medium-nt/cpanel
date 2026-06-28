<?php

use App\Http\Controllers\TicketController;

Route::resource('tickets', TicketController::class)
    ->only(['index', 'create', 'store', 'show']);

Route::put('tickets/{ticket}/close', [TicketController::class, 'close'])
    ->name('tickets.close');
Route::put('tickets/{ticket}/delete', [TicketController::class, 'delete'])
    ->name('tickets.delete');
