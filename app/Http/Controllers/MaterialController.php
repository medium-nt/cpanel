<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\TypeMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'purchase_price' => 'required|numeric|min:0.01',
        ];

        $validatedData = $request->validate($rules);
        $material = Material::query()->create($validatedData);

        Log::channel('materials')->info('Создан материал', [
            'material_id' => $material->id,
            'title' => $material->title,
            'type_id' => $material->type_id,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('materials.index')->with('success', 'Материал добавлен');
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
            'purchase_price' => 'required|numeric|min:0.01',
            'is_active' => 'required|boolean',
        ];

        $validatedData = $request->validate($rules);
        $material->update($validatedData);

        Log::channel('materials')->info('Обновлён материал', [
            'material_id' => $material->id,
            'changed' => collect($material->getChanges())->except(['updated_at'])->keys(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('materials.index')->with('success', 'Изменения сохранены');
    }

    public function destroy(Material $material)
    {
        if ($material->movementMaterials()->count() > 0) {
            return redirect()->route('materials.index')
                ->with('error', 'Невозможно удалить материал, так как он используется в системе');
        }

        Log::channel('materials')->warning('Удалён материал', [
            'material_id' => $material->id,
            'title' => $material->title,
            'deleted_by' => auth()->id(),
        ]);

        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Материал удален');
    }
}
