<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MaterialSupplierController extends Controller
{
    /**
     * Привязать поставщика к материалу.
     * POST /megatulle/materials/{material}/suppliers
     */
    public function attach(Request $request, Material $material)
    {
        $validated = $request->validate([
            'supplier_id' => [
                'required',
                'exists:suppliers,id',
                Rule::unique('material_supplier', 'supplier_id')->where(fn ($q) => $q->where('material_id', $material->id)),
            ],
        ], [
            'supplier_id.required' => 'Выберите поставщика.',
            'supplier_id.exists' => 'Поставщик не найден.',
            'supplier_id.unique' => 'Этот поставщик уже привязан к материалу.',
        ]);

        $supplier = Supplier::findOrFail($validated['supplier_id']);

        $material->suppliers()->attach($supplier->id, [
            'shortage_percent' => 0,
        ]);

        Log::channel('materials')->info('Привязан поставщик к материалу', [
            'material_id' => $material->id,
            'material_title' => $material->title,
            'supplier_id' => $supplier->id,
            'supplier_title' => $supplier->title,
            'attached_by' => auth()->id(),
        ]);

        return redirect()->back()
            ->with('success', "Поставщик \"{$supplier->title}\" добавлен");
    }

    /**
     * Массовое обновление процента недостачи.
     * PUT /megatulle/materials/{material}/suppliers
     */
    public function updateShortages(Request $request, Material $material)
    {
        $validated = $request->validate([
            'shortages' => 'required|array',
            'shortages.*' => 'required|numeric|min:0|max:100',
        ], [
            'shortages.required' => 'Нет данных для обновления.',
            'shortages.*.numeric' => 'Значение должно быть числом.',
            'shortages.*.min' => 'Процент не может быть отрицательным.',
            'shortages.*.max' => 'Процент не может превышать 100.',
        ]);

        $changes = [];

        foreach ($validated['shortages'] as $pivotId => $shortagePercent) {
            $pivot = DB::table('material_supplier')
                ->where('id', $pivotId)
                ->where('material_id', $material->id)
                ->first();

            if (! $pivot) {
                continue;
            }

            if ($pivot->shortage_percent != $shortagePercent) {
                DB::table('material_supplier')
                    ->where('id', $pivotId)
                    ->where('material_id', $material->id)
                    ->update([
                        'shortage_percent' => $shortagePercent,
                        'updated_at' => now(),
                    ]);

                $changes[] = [
                    'supplier_id' => $pivot->supplier_id,
                    'old_shortage' => $pivot->shortage_percent,
                    'new_shortage' => $shortagePercent,
                ];
            }
        }

        Log::channel('materials')->info('Обновлён недостача материалов', [
            'material_id' => $material->id,
            'material_title' => $material->title,
            'changes' => $changes,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()
            ->with('success', 'Процент недостачи обновлён');
    }

    /**
     * Отвязать поставщика от материала.
     * DELETE /megatulle/materials/{material}/suppliers/{pivotId}
     */
    public function detach(Request $request, Material $material, int $pivotId)
    {
        $pivot = DB::table('material_supplier')
            ->where('id', $pivotId)
            ->where('material_id', $material->id)
            ->first();

        if (! $pivot) {
            return redirect()->back()
                ->with('error', 'Связь материала и поставщика не найдена');
        }

        $supplier = Supplier::find($pivot->supplier_id);
        $supplierTitle = $supplier?->title ?? 'Неизвестный';

        $material->suppliers()->detach($pivot->supplier_id);

        Log::channel('materials')->warning('Отвязан поставщик от материала', [
            'material_id' => $material->id,
            'material_title' => $material->title,
            'supplier_id' => $pivot->supplier_id,
            'supplier_title' => $supplierTitle,
            'detached_by' => auth()->id(),
        ]);

        return redirect()->back()
            ->with('success', "Поставщик \"{$supplierTitle}\" удалён");
    }
}
