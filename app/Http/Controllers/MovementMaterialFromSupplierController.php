<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovementMaterialFromSupplierRequest;
use App\Http\Requests\UpdateMovementMaterialFromSupplierRequest;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\MovementMaterialFromSupplierService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MovementMaterialFromSupplierController extends Controller
{
    public function index()
    {
        return view('movements_from_supplier.index', [
            'title' => 'Поступление материалов на склад',
            'orders' => Order::query()
                ->where('type_movement', 1)
                ->when(request()->has('status'), function ($query) {
                    return $query->where('status', request('status'));
                })
                ->latest()
                ->paginate(10)
                ->withQueryString(),
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
        if (! MovementMaterialFromSupplierService::store($request)) {
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
            'suppliers' => Supplier::query()->get(),
        ]);
    }

    public function update(UpdateMovementMaterialFromSupplierRequest $request, Order $order)
    {
        if (! MovementMaterialFromSupplierService::update($request, $order)) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect()
            ->route('movements_from_supplier.index')
            ->with('success', 'Поступление добавлено');
    }

    /**
     * @throws Throwable
     */
    public function destroy(Order $order)
    {
        if ($order->status != 0) {
            return back()
                ->with('error', 'Невозможно удалить! Поставка уже отгружена');
        }

        $text = 'Админ удалил поставку '.$order->id.' с товарами: '."\n";
        foreach ($order->movementMaterials as $movement) {
            $text .= '•'.$movement->material->title.' '.$movement->quantity.' '.$movement->material->unit."\n";
        }

        DB::transaction(function () use ($order) {
            $materials = $order->movementMaterials;

            $order->movementMaterials()->delete();

            $materials->each(function (MovementMaterial $material) {
                $material->roll()->delete();
            });

            $order->delete();
        });

        Log::channel('erp')
            ->warning($text);

        return redirect()
            ->route('movements_from_supplier.index')
            ->with('success', 'Поступление удалено');
    }
}
