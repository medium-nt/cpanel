<?php

use App\Models\Schedule;

Route::prefix('/schedule')->group(function () {
    Route::post('/changeDate', [App\Http\Controllers\ScheduleController::class, 'changeDate'])
        ->can('changeDate', Schedule::class)
        ->name('schedule.changeDate');
});
