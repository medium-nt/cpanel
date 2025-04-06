<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MovementMaterialToWorkshopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        match ($request->status)
        {
            'all' => $status = [-1, 0, 1, 2, 3],
            default => $status = [0, 2],
        };

        return view('movements_to_workshop.index', [
            'title' => 'Отгрузка на производство',
            'userRole' => auth()->user()->role->name,
            'orders' => Order::query()
                ->where('type_movement', 2)
                ->whereIn('status', $status)
                ->paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('movements_to_workshop.create', [
            'title' => 'Заказ новых материалов на производство',
            'materials' => Material::query()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!is_array($request->material_id)) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Заказ не может быть пустым'])
                ->withInput();
        }

        $data = [];
        foreach ($request->material_id as $key => $material_id) {
            if ($request->ordered_quantity[$key] > 0) {
                $data[] = [
                    'material_id' => $material_id,
                    'ordered_quantity' => $request->ordered_quantity[$key]
                ];
            }
        }

        if ($data == []) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Заказ не может быть пустым'])
                ->withInput();
        }

        $rules = [
            '*.material_id' => 'required|exists:materials,id',
            '*.ordered_quantity' => 'required|integer|min:1',
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
            'seamstress_id' => auth()->user()->id,
            'type_movement' => 2,
            'status' => 0
        ]);

        foreach ($validatedData as $item) {
            $movementData['order_id'] = $order->id;
            $movementData['material_id'] = $item['material_id'];
            $movementData['ordered_quantity'] = $item['ordered_quantity'];

            MovementMaterial::query()->create($movementData);
        }

        return redirect()
            ->route('movements_to_workshop.index')
            ->with('success', 'Заказ сформирован и отправлен на склад');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function collect(Order $order)
    {
        return view('movements_to_workshop.collect', [
            'title' => 'Сборка поставки',
            'order' => $order,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function save_collect(Request $request, Order $order)
    {
        $data = [];
        foreach ($request->material_id as $key => $material_id) {
            $data[] = [
                'id' => $request->id[$key],
                'quantity' => $request->quantity[$key]
            ];
        }

        $rules = [
            '*.id' => 'required|exists:movement_materials,id',
            '*.quantity' => 'required|integer',
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
                    'quantity' => $item['quantity'],
                ]);
        }

        $order->update([
            'status' => 2,
            'storekeeper_id' => auth()->user()->id
        ]);

        return redirect()->route('movements_to_workshop.index')->with('success', 'Отгрузка сформирована');
    }

    public function receive(Order $order)
    {
        return view('movements_to_workshop.receive', [
            'title' => 'Прием поставки',
            'order' => $order,
        ]);
    }

    public function save_receive(Request $request, Order $order)
    {
        $order->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        return redirect()->route('movements_to_workshop.index')->with('success', 'Поставка принята');
    }
}
