<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDefectMaterialToSupplierRequest;
use App\Http\Requests\UpdateMovementMaterialFromSupplierRequest;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\MovementDefectMaterialToSupplierService;
use App\Services\MovementMaterialFromSupplierService;
use Illuminate\Http\Request;

class MovementDefectMaterialToSupplierController extends Controller
{
    public function index()
    {
        return view('movements_defect_to_supplier.index', [
            'title' => 'Возврат брака поставщику',
            'orders' => Order::query()
                ->where('type_movement', 5)
                ->latest()
                ->paginate(10)
        ]);
    }

    public function create()
    {
        return view('movements_defect_to_supplier.create', [
            'title' => 'Добавить отгрузку брака поставщику',
            'materials' => Material::query()->get(),
            'suppliers' => Supplier::query()->get(),
        ]);
    }

    public function store(StoreDefectMaterialToSupplierRequest $request)
    {
        if(!MovementDefectMaterialToSupplierService::store($request)) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect()
            ->route('movements_defect_to_supplier.index')
            ->with('success', 'Поступление добавлено');
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
