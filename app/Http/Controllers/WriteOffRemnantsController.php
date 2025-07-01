<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRemnantsRequest;
use App\Models\Material;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\WriteOffRemnantService;

class WriteOffRemnantsController extends Controller
{
    public function index()
    {
        return view('write_off_remnants.index', [
            'title' => 'Списание остатков',
            'orders' => Order::query()
                ->where('type_movement', 8)
                ->latest()
                ->paginate(10)
        ]);
    }

    public function create()
    {
        return view('write_off_remnants.create', [
            'title' => 'Добавить списание остатков',
            'materials' => Material::query()->get(),
            'suppliers' => Supplier::query()->get(),
        ]);
    }

    public function store(StoreRemnantsRequest $request)
    {
        if(!WriteOffRemnantService::store($request)) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect()
            ->route('write_off_remnants.index')
            ->with('success', 'Остатки успешно списаны');
    }

//    public function edit(Order $order)
//    {
//        return view('movements_from_supplier.edit', [
//            'title' => 'Изменить поставку',
//            'order' => $order,
//            'materials' => Material::query()->get(),
//        ]);
//    }
//
//    public function update(UpdateMovementMaterialFromSupplierRequest $request, Order $order)
//    {
//        if(!MovementMaterialFromSupplierService::update($request, $order)) {
//            return back()->withErrors(['error' => 'Внутренняя ошибка']);
//        }
//
//        return redirect()
//            ->route('movements_from_supplier.index')
//            ->with('success', 'Поступление добавлено');
//    }
//
//    public function destroy(MovementMaterial $movementMaterial)
//    {
//        //
//    }
}
