<?php

use App\Http\Controllers\MaterialConsumptionController;

Route::prefix('/material_consumption')->group(function () {
    Route::get('/delete/{material_consumption}', [MaterialConsumptionController::class, 'destroy'])
        ->can('delete', 'material_consumption')
        ->name('material_consumption.destroy');
});
