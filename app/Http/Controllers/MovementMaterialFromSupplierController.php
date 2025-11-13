<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovementMaterialFromSupplierRequest;
use App\Http\Requests\UpdateMovementMaterialFromSupplierRequest;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\MovementMaterialFromSupplierService;

class MovementMaterialFromSupplierController extends Controller
{
    public function index()
    {
        return view('movements_from_supplier.index', [
            'title' => 'Поступление материалов на склад',
            'orders' => Order::query()
                ->where('type_movement', 1)
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create()
    {
        return view('movements_from_supplier.create', [
            'title' => 'Добавить поступление на склад',
            'materials' => Material::query()->get(),
            'suppliers' => Supplier::query()->get(),
        ]);
    }

    public function store(StoreMovementMaterialFromSupplierRequest $request)
    {
        if (!MovementMaterialFromSupplierService::store($request)) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect()
            ->route('movements_from_supplier.index')
            ->with('success', 'Поступление добавлено');
    }

    public function show(MovementMaterial $movementMaterial)
    {
        //
    }

    public function edit(Order $order)
    {
        return view('movements_from_supplier.edit', [
            'title' => 'Изменить поставку',
            'order' => $order,
            'materials' => Material::query()->get(),
        ]);
    }

    public function update(UpdateMovementMaterialFromSupplierRequest $request, Order $order)
    {
        if (!MovementMaterialFromSupplierService::update($request, $order)) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect()
            ->route('movements_from_supplier.index')
            ->with('success', 'Поступление добавлено');
    }

    public function destroy(MovementMaterial $movementMaterial)
    {
        //
    }
}
