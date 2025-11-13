<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

if (App::environment(['local'])) {
    Route::prefix('autologin')->group(function () {
        Route::get('/{email}', [App\Http\Controllers\UsersController::class, 'autologin'])
            ->name('users.autologin');
    });
}

Route::get('/sticker_printing', [App\Http\Controllers\StickerPrintingController::class, 'index'])->name('sticker_printing');

Route::get('/open_close_work_shift', [App\Http\Controllers\StickerPrintingController::class, 'openCloseWorkShift'])
    ->name('open_close_work_shift');

Route::get('/open_close_work_shift_admin/{user}', [App\Http\Controllers\StickerPrintingController::class, 'openCloseWorkShiftAdmin'])
    ->can('update', User::class)
    ->name('open_close_work_shift_admin');

Route::get('barcode', [App\Http\Controllers\MarketplaceApiController::class, 'getBarcodeFile'])
    ->name('marketplace_api.barcode');

Route::get('fbo_barcode', [App\Http\Controllers\MarketplaceApiController::class, 'getFBOBarcodeFile'])
    ->name('marketplace_api.fbo_barcode');

Route::put('/done/{marketplace_order_item}', [App\Http\Controllers\MarketplaceOrderItemController::class, 'done'])
    ->name('marketplace_order_items.done');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

//  доступно без начала смены.
Route::prefix('megatulle')
    ->middleware('auth')
    ->group(function () {
        require base_path('routes/transactions.php');
        require base_path('routes/profile.php');
        require base_path('routes/users.php');
    });

//  доступно только после начала смены.
Route::prefix('megatulle')
    ->middleware('auth')
    ->middleware('require_open_shift')
    ->group(function () {
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
        //        require base_path('routes/salary.php');
        require base_path('routes/movements_by_marketplace_order.php');
        require base_path('routes/schedule.php');
        require base_path('routes/movements_defect_to_supplier.php');
        require base_path('routes/marketplace_api.php');
        require base_path('routes/setting.php');
        require base_path('routes/write_off_remnants.php');
        require base_path('routes/marketplace_supplies.php');
        require base_path('routes/warehouse_of_item.php');
        require base_path('routes/shelves.php');
    });
