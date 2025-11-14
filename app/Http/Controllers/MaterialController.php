<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\TypeMaterial;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index()
    {
        return view('materials.index', [
            'title' => 'Материалы',
            'materials' => Material::query()->paginate(10),
        ]);
    }

    public function create()
    {
        return view('materials.create', [
            'title' => 'Добавить материал',
            'typesMaterial' => TypeMaterial::query()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|unique:materials,title|min:2|max:255',
            'type_id' => 'required|integer|exists:type_materials,id',
            'unit' => 'required|string|min:1|max:10',
        ];

        $validatedData = $request->validate($rules);
        Material::query()->create($validatedData);

        return redirect()->route('materials.index')->with('success', 'Материал добавлен');
    }

    public function show(Material $material)
    {
        //
    }

    public function edit(Material $material)
    {
        return view('materials.edit', [
            'title' => 'Изменить материал',
            'material' => $material,
            'typesMaterial' => TypeMaterial::query()->get(),
        ]);
    }

    public function update(Request $request, Material $material)
    {
        $rules = [
            'title' => 'required|string|min:2|max:255',
            'type_id' => 'required|integer|exists:type_materials,id',
            'unit' => 'required|string|min:1|max:10',
        ];

        $validatedData = $request->validate($rules);
        $material->update($validatedData);

        return redirect()->route('materials.index')->with('success', 'Изменения сохранены');
    }

    public function destroy(Material $material)
    {
        if ($material->movementMaterials()->count() > 0) {
            return redirect()->route('materials.index')
                ->with('error', 'Невозможно удалить материал, так как он используется в системе');
        }

        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Материал удален');
    }
}
