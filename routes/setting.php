<?php

use App\Http\Controllers\SettingController;
use App\Models\Setting;

Route::prefix('/setting')->group(function () {
    Route::get('', [SettingController::class, 'index'])
        ->can('viewAny', Setting::class)
        ->name('setting.index');

    Route::post('save', [SettingController::class, 'save'])
        ->can('update', Setting::class)
        ->name('setting.save');

    Route::get('test', [SettingController::class, 'test'])
        ->can('update', Setting::class)
        ->name('setting.test');

    Route::get('duplicates', [SettingController::class, 'duplicates'])
        ->can('update', Setting::class)
        ->name('setting.duplicates');

    Route::get('sync-warehouses-ozon', [SettingController::class, 'syncWarehousesOzon'])
        ->can('update', Setting::class)
        ->name('setting.syncWarehousesOzon');

    Route::get('sync-warehouses-wb', [SettingController::class, 'syncWarehousesWb'])
        ->can('update', Setting::class)
        ->name('setting.syncWarehousesWb');
});
