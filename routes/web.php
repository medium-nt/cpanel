<?php

use App\Models\Material;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::prefix('admin')->middleware('auth')->group(function () {

    require base_path('routes/users.php');
    require base_path('routes/profile.php');
    require base_path('routes/materials.php');
    require base_path('routes/suppliers.php');
    require base_path('routes/movements_from_supplier.php');
    require base_path('routes/movements_to_workshop.php');
    require base_path('routes/inventory.php');
    require base_path('routes/marketplace_items.php');
    require base_path('routes/marketplace_orders.php');
    require base_path('routes/marketplace_order_items.php');
    require base_path('routes/material_consumption.php');
    require base_path('routes/defect_materials.php');

});
