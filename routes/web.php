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

    Route::prefix('/movements')->group(function () {
        Route::get('', [App\Http\Controllers\MovementMaterialController::class, 'index'])
            ->can('viewAny', App\Models\MovementMaterial::class)
            ->name('movements.index');

        Route::get('/create', [App\Http\Controllers\MovementMaterialController::class, 'create'])
            ->can('create', App\Models\MovementMaterial::class)
            ->name('movements.create');

        Route::post('/store', [App\Http\Controllers\MovementMaterialController::class, 'store'])
            ->can('create', App\Models\MovementMaterial::class)
            ->name('movements.store');

        Route::get('/{movement}/edit', [App\Http\Controllers\MovementMaterialController::class, 'edit'])
            ->can('update', 'movement')
            ->name('movements.edit');

        Route::put('/update/{movement}', [App\Http\Controllers\MovementMaterialController::class, 'update'])
            ->can('update', 'movement')
            ->name('movements.update');

        Route::delete('/delete/{movement}', [App\Http\Controllers\MovementMaterialController::class, 'destroy'])
            ->can('delete', 'movement')
            ->name('movements.destroy');
    });
});
