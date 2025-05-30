<?php

use App\Models\Setting;

Route::prefix('/setting')->group(function () {
    Route::get('', [App\Http\Controllers\SettingController::class, 'index'])
        ->can('viewAny', Setting::class)
        ->name('setting.index');

    Route::post('save', [App\Http\Controllers\SettingController::class, 'save'])
        ->can('update', Setting::class)
        ->name('setting.save');

    Route::get('test', [App\Http\Controllers\SettingController::class, 'test'])
        ->can('update', Setting::class)
        ->name('setting.test');
});
