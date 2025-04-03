<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Supplier;
use Illuminate\Http\Request;

class MovementMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('movements.index', [
            'title' => 'Поступление материалов на склад',
            'movements' => MovementMaterial::query()->where('type_movement', 1)->paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('movements.create', [
            'title' => 'Добавить поступление на склад',
            'materials' => Material::query()->get(),
            'suppliers' => Supplier::query()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'supplier_id' => 'required|exists:suppliers,id',
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|integer',
        ];

        $validatedData = $request->validate($rules);

        $validatedData['type_movement'] = 1;
        $validatedData['status_movement'] = 1;
        $validatedData['storekeeper_id'] = auth()->user()->id;
        $validatedData['completed_at'] = now();

        MovementMaterial::query()->create($validatedData);

        return redirect()->route('movements.index')->with('success', 'Поступление добавлено');
    }

    /**
     * Display the specified resource.
     */
    public function show(MovementMaterial $movementMaterial)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MovementMaterial $movementMaterial)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MovementMaterial $movementMaterial)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MovementMaterial $movementMaterial)
    {
        //
    }
}
