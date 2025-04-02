<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\TypeMaterial;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('materials.index', [
            'title' => 'Материалы',
            'materials' => Material::query()->paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('materials.create', [
            'title' => 'Добавить материал',
            'typesMaterial' => TypeMaterial::query()->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|min:2|max:255',
            'type_id' => 'required|integer|exists:type_materials,id',
            'height' => 'required|integer',
            'unit' => 'required|string|min:1|max:10',
        ];

        $validatedData = $request->validate($rules);
        Material::query()->create($validatedData);

        return redirect()->route('materials.index')->with('success', 'Материал добавлен');
    }

    /**
     * Display the specified resource.
     */
    public function show(Material $material)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Material $material)
    {
        return view('materials.edit', [
            'title' => 'Изменить материал',
            'material' => $material,
            'typesMaterial' => TypeMaterial::query()->get()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Material $material)
    {
        $rules = [
            'title' => 'required|string|min:2|max:255',
            'type_id' => 'required|integer|exists:type_materials,id',
            'height' => 'required|integer',
            'unit' => 'required|string|min:1|max:10',
        ];

        $validatedData = $request->validate($rules);
        $material->update($validatedData);

        return redirect()->route('materials.index')->with('success', 'Изменения сохранены');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material)
    {
        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Материал удален');
    }
}
