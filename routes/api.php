<?php

use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);
// Route::get('/telegram/webhook', [TelegramController::class, 'webhook']);
