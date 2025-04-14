<?php

Route::prefix('/material_consumption')->group(function () {
    Route::get('/delete/{material_consumption}', [App\Http\Controllers\MaterialConsumptionController::class, 'destroy'])
        ->can('delete', 'material_consumption')
        ->name('material_consumption.destroy');
});
