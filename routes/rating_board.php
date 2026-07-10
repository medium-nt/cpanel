<?php

use App\Http\Controllers\RatingBoardController;
use Illuminate\Support\Facades\Route;

// Доступ по токену в URL, без auth.
Route::get('/rating_board/{token}/{workshop}', [RatingBoardController::class, 'index'])
    ->name('rating_board.index');

Route::get('/rating_board/{token}/{workshop}/data', [RatingBoardController::class, 'data'])
    ->name('rating_board.data');
