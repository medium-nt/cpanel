<?php

use App\Http\Controllers\ScheduleController;
use App\Models\Schedule;

Route::prefix('/schedule')->group(function () {
    Route::post('/changeDate', [ScheduleController::class, 'changeDate'])
        ->can('changeDate', Schedule::class)
        ->name('schedule.changeDate');
});
