<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovementMaterialFromSupplierRequest;
use App\Http\Requests\UpdateMovementMaterialFromSupplierRequest;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\MovementMaterialFromSupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MovementMaterialFromSupplierController extends Controller
{
    /**
     * Список поступлений материалов от поставщика с фильтрами по статусу,
     * поставщику и диапазону дат создания.
     */
    public function index(Request $request)
    {
        return view('movements_from_supplier.index', [
            'title' => 'Поступление материалов на склад',
            'suppliers' => Supplier::orderBy('title')->pluck('title', 'id'),
            'orders' => Order::query()
                ->where('type_movement', 1)
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->integer('status')))
                ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
                ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
                ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
                ->with(['movementMaterials.material', 'movementMaterials.roll', 'user', 'supplier'])
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('movements_from_supplier.create', [
            'title' => 'Добавить поступление на склад',
            'materials' => Material::active()->get(),
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
            'materials' => Material::active()->get(),
            'suppliers' => Supplier::query()->get(),
        ]);
    }

    public function update(UpdateMovementMaterialFromSupplierRequest $request, Order $order)
    {
        if (! MovementMaterialFromSupplierService::update($request, $order)) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        $message = $request->input('action') === 'complete'
            ? 'Поступление завершено'
            : 'Изменения сохранены';

        return redirect()
            ->route('movements_from_supplier.index')
            ->with('success', $message);
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

        Log::channel('materials')
            ->warning($text);

        return redirect()
            ->route('movements_from_supplier.index')
            ->with('success', 'Поступление удалено');
    }
}
