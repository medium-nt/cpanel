<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MovementMaterialFromSupplierController extends Controller
{
    public function index()
    {
        return view('movements_from_supplier.index', [
            'title' => 'Поступление материалов на склад',
            'orders' => Order::query()
                ->where('type_movement', 1)
                ->paginate(10)
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

    public function store(Request $request)
    {
        $data = [];
        foreach ($request->material_id as $key => $material_id) {
            if ($request->quantity[$key] > 0) {
                $data[] = [
                    'supplier_id' => $request->supplier_id,
                    'material_id' => $material_id,
                    'quantity' => $request->quantity[$key]
                ];
            }
        }

        $rules = [
            '*.supplier_id' => 'required|exists:suppliers,id',
            '*.material_id' => 'required|exists:materials,id',
            '*.quantity' => 'required|numeric|min:0.01',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated();

        $order = Order::query()->create([
            'supplier_id' => $validatedData[0]['supplier_id'],
            'storekeeper_id' => auth()->user()->id,
            'type_movement' => 1,
            'status' => 3,
            'comment' => $request->comment,
            'completed_at' => now()
        ]);

        foreach ($validatedData as $item) {
            MovementMaterial::query()->create([
                'order_id' => $order->id,
                'material_id' => $item['material_id'],
                'quantity' => $item['quantity'],
            ]);
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

    public function update(Request $request, Order $order)
    {
        $data = [];
        foreach ($request->material_id as $key => $material_id) {
            $data[] = [
                'id' => $request->id[$key],
                'price' => $request->price[$key]
            ];
        }

        $rules = [
            '*.id' => 'required|exists:movement_materials,id',
            '*.price' => 'required|integer',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated();

        foreach ($validatedData as $item) {
            MovementMaterial::query()
                ->where('id', $item['id'])
                ->update([
                    'price' => $item['price'],
                ]);
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
