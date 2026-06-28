<?php

use App\Http\Controllers\MaxController;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);
// Route::get('/telegram/webhook', [TelegramController::class, 'webhook']);

Route::post('/max/webhook', [MaxController::class, 'webhook']);
