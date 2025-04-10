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

    Route::prefix('/profile')->group(function () {
        Route::get('', [App\Http\Controllers\UsersController::class, 'profile'])->name('profile');
        Route::put('', [App\Http\Controllers\UsersController::class, 'profileUpdate'])->name('profile.update');
    });

    Route::prefix('/users')->group(function () {
        Route::get('', [App\Http\Controllers\UsersController::class, 'index'])
            ->can('viewAny', User::class)->name('users.index');

        Route::get('/create', [App\Http\Controllers\UsersController::class, 'create'])
            ->can('create', User::class)->name('users.create');

        Route::post('/store', [App\Http\Controllers\UsersController::class, 'store'])
            ->can('create', User::class)->name('users.store');

        Route::get('/{user}/edit', [App\Http\Controllers\UsersController::class, 'edit'])
            ->can('update', 'user')->name('users.edit');

        Route::put('/update/{user}', [App\Http\Controllers\UsersController::class, 'update'])
            ->can('update', 'user')->name('users.update');

        Route::delete('/delete/{user}', [App\Http\Controllers\UsersController::class, 'destroy'])
            ->can('delete', 'user')->name('users.destroy');
    });

    Route::prefix('/materials')->group(function () {
        Route::get('', [App\Http\Controllers\MaterialController::class, 'index'])
            ->can('viewAny', Material::class)
            ->name('materials.index');

        Route::get('/create', [App\Http\Controllers\MaterialController::class, 'create'])
            ->can('create', Material::class)
            ->name('materials.create');

        Route::post('/store', [App\Http\Controllers\MaterialController::class, 'store'])
            ->can('create', Material::class)
            ->name('materials.store');

        Route::get('/{material}/edit', [App\Http\Controllers\MaterialController::class, 'edit'])
            ->can('update', 'material')
            ->name('materials.edit');

        Route::put('/update/{material}', [App\Http\Controllers\MaterialController::class, 'update'])
            ->can('update', 'material')
            ->name('materials.update');

        Route::delete('/delete/{material}', [App\Http\Controllers\MaterialController::class, 'destroy'])
            ->can('delete', 'material')
            ->name('materials.destroy');
    });

    Route::prefix('/suppliers')->group(function () {
        Route::get('', [App\Http\Controllers\SupplierController::class, 'index'])
            ->can('viewAny', App\Models\Supplier::class)
            ->name('suppliers.index');

        Route::get('/create', [App\Http\Controllers\SupplierController::class, 'create'])
            ->can('create', App\Models\Supplier::class)
            ->name('suppliers.create');

        Route::post('/store', [App\Http\Controllers\SupplierController::class, 'store'])
            ->can('create', App\Models\Supplier::class)
            ->name('suppliers.store');

        Route::get('/{supplier}/edit', [App\Http\Controllers\SupplierController::class, 'edit'])
            ->can('update', 'supplier')
            ->name('suppliers.edit');

        Route::put('/update/{supplier}', [App\Http\Controllers\SupplierController::class, 'update'])
            ->can('update', 'supplier')
            ->name('suppliers.update');

        Route::delete('/delete/{supplier}', [App\Http\Controllers\SupplierController::class, 'destroy'])
            ->can('delete', 'supplier')
            ->name('suppliers.destroy');
    });

    Route::prefix('/movements_from_supplier')->group(function () {
        Route::get('', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'index'])
            ->can('viewAny', App\Models\MovementMaterial::class)
            ->name('movements_from_supplier.index');

        Route::get('/create', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'create'])
            ->can('create', App\Models\MovementMaterial::class)
            ->name('movements_from_supplier.create');

        Route::post('/store', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'store'])
            ->can('create', App\Models\MovementMaterial::class)
            ->name('movements_from_supplier.store');

        Route::get('/{order}/edit', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'edit'])
            ->can('update', 'order')
            ->name('movements_from_supplier.edit');

        Route::put('/update/{order}', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'update'])
            ->name('movements_from_supplier.update');

        Route::delete('/delete/{movement}', [App\Http\Controllers\MovementMaterialFromSupplierController::class, 'destroy'])
            ->can('delete', 'movement')
            ->name('movements_from_supplier.destroy');
    });

    Route::prefix('/movements_to_workshop')->group(function () {
        Route::get('', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'index'])
            ->can('viewAny', App\Models\MovementMaterial::class)
            ->name('movements_to_workshop.index');

        Route::get('/create', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'create'])
            ->can('create', App\Models\MovementMaterial::class)
            ->name('movements_to_workshop.create');

        Route::post('/store', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'store'])
            ->can('create', App\Models\MovementMaterial::class)
            ->name('movements_to_workshop.store');

        Route::get('/{order}/collect', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'collect'])
            ->can('update', 'order')
            ->name('movements_to_workshop.collect');

        Route::put('/save_collect/{order}', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'save_collect'])
            ->can('update', 'order')
            ->name('movements_to_workshop.save_collect');

        Route::get('/{order}/receive', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'receive'])
            ->can('update', App\Models\MovementMaterial::class)
            ->name('movements_to_workshop.receive');

        Route::put('/save_receive/{order}', [App\Http\Controllers\MovementMaterialToWorkshopController::class, 'save_receive'])
            ->can('update', 'order')
            ->name('movements_to_workshop.save_receive');
    });

    Route::prefix('/inventory')->group(function () {
        Route::get('/warehouse', [App\Http\Controllers\InventoryController::class, 'byWarehouse'])
            ->can('viewAny', App\Models\MovementMaterial::class)
            ->name('inventory.warehouse');

        Route::get('/workshop', [App\Http\Controllers\InventoryController::class, 'byWorkshop'])
            ->can('viewAny', App\Models\MovementMaterial::class)
            ->name('inventory.workshop');
    });

    Route::prefix('/marketplace_items')->group(function () {
        Route::get('', [App\Http\Controllers\MarketplaceItemController::class, 'index'])
            ->can('viewAny', App\Models\MarketplaceItem::class)
            ->name('marketplace_items.index');

        Route::get('/create', [App\Http\Controllers\MarketplaceItemController::class, 'create'])
            ->can('create', App\Models\MarketplaceItem::class)
            ->name('marketplace_items.create');

        Route::post('/store', [App\Http\Controllers\MarketplaceItemController::class, 'store'])
            ->can('create', App\Models\MarketplaceItem::class)
            ->name('marketplace_items.store');

        Route::get('/{marketplace_item}/edit', [App\Http\Controllers\MarketplaceItemController::class, 'edit'])
            ->can('update', 'marketplace_item')
            ->name('marketplace_items.edit');

        Route::put('/update/{marketplace_item}', [App\Http\Controllers\MarketplaceItemController::class, 'update'])
            ->can('update', 'marketplace_item')
            ->name('marketplace_items.update');

        Route::delete('/delete/{marketplace_item}', [App\Http\Controllers\MarketplaceItemController::class, 'destroy'])
            ->can('delete', 'marketplace_item')
            ->name('marketplace_items.destroy');
    });

    Route::prefix('/marketplace_orders')->group(function () {
        Route::get('', [App\Http\Controllers\MarketplaceOrderController::class, 'index'])
            ->can('viewAny', App\Models\MarketplaceOrder::class)
            ->name('marketplace_orders.index');

        Route::get('/create', [App\Http\Controllers\MarketplaceOrderController::class, 'create'])
            ->can('create', App\Models\MarketplaceOrder::class)
            ->name('marketplace_orders.create');

        Route::post('/store', [App\Http\Controllers\MarketplaceOrderController::class, 'store'])
            ->can('create', App\Models\MarketplaceOrder::class)
            ->name('marketplace_orders.store');

        Route::get('/{marketplace_order}/edit', [App\Http\Controllers\MarketplaceOrderController::class, 'edit'])
            ->can('update', 'marketplace_order')
            ->name('marketplace_orders.edit');

        Route::put('/update/{marketplace_order}', [App\Http\Controllers\MarketplaceOrderController::class, 'update'])
            ->can('update', 'marketplace_order')
            ->name('marketplace_orders.update');

        Route::delete('/delete/{marketplace_order}', [App\Http\Controllers\MarketplaceOrderController::class, 'destroy'])
            ->can('delete', 'marketplace_order')
            ->name('marketplace_orders.destroy');
    });

    Route::prefix('/marketplace_order_items')->group(function () {
        Route::get('', [App\Http\Controllers\MarketplaceOrderItemController::class, 'index'])
            ->can('viewAny', App\Models\MarketplaceItem::class)
            ->name('marketplace_order_items.index');

        Route::get('/{marketplace_order_items}/edit', [App\Http\Controllers\MarketplaceOrderItemController::class, 'edit'])
            ->can('update', 'marketplace_order_items')
            ->name('marketplace_order_items.edit');

        Route::put('/startWork/{marketplace_order_item}', [App\Http\Controllers\MarketplaceOrderItemController::class, 'startWork'])
            ->can('update', 'marketplace_order_item')
            ->name('marketplace_order_items.startWork');

        Route::put('/done/{marketplace_order_item}', [App\Http\Controllers\MarketplaceOrderItemController::class, 'done'])
            ->can('update', 'marketplace_order_item')
            ->name('marketplace_order_items.done');
    });

    Route::prefix('/material_consumption')->group(function () {
        Route::get('/delete/{material_consumption}', [App\Http\Controllers\MaterialConsumptionController::class, 'destroy'])
            ->can('delete', 'material_consumption')
            ->name('material_consumption.destroy');
    });
});
