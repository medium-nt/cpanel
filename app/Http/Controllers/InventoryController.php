<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryRequest;
use App\Models\InventoryCheck;
use App\Models\Shelf;
use App\Models\TypeMaterial;
use App\Services\InventoryService;
use App\Services\ShiftService;

class InventoryController extends Controller
{
    public function byWarehouse()
    {
        return view('inventory.warehouse', [
            'title' => 'Материал на складе',
            'sections' => $this->groupMaterialsByType(
                InventoryService::materialsQuantityBy('warehouse')
            ),
        ]);
    }

    /**
     * Группирует материалы (склада или цеха) в секции по типу материала.
     * Порядок секций берётся из таблицы type_materials — новые типы подхватываются
     * автоматически. Материалы без типа попадают в секцию «Прочее» в конце.
     *
     * @param  array  $materials  плоский массив элементов с ключом ['material']->type_id
     * @return array<int, array{title: string, items: array}>
     */
    private function groupMaterialsByType(array $materials): array
    {
        $groups = collect($materials)->groupBy(fn (array $item) => $item['material']->type_id);

        $sections = [];
        foreach (TypeMaterial::orderBy('id')->get() as $type) {
            if ($items = $groups->get($type->id)) {
                $sections[] = ['title' => $type->title, 'items' => $items->all()];
            }
        }
        // Материалы без типа — отдельной секцией «Прочее» в конце
        if ($items = $groups->get(null)) {
            $sections[] = ['title' => 'Прочее', 'items' => $items->all()];
        }

        return $sections;
    }

    public function byWorkshop()
    {
        $data = InventoryService::materialsQuantityByWorkshopPerShift(auth()->user());

        return view('inventory.workshop', [
            'title' => 'Материал на производстве',
            'shifts' => $data['shifts'],
            'sections' => $this->groupMaterialsByType($data['materials']),
            'todayShiftIds' => ShiftService::getTodayScheduledShifts()->pluck('id')->toArray(),
        ]);
    }

    public function inventoryChecks()
    {
        $status = request('status') ?? 'in_progress';

        return view('inventory.index', [
            'title' => 'Инвентаризации',
            'inventories' => InventoryCheck::query()
                ->where('status', $status)
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(InventoryCheck $inventory)
    {
        $labels = [
            'in_progress' => 'В процессе',
            'closed' => 'Закрыта',
        ];

        $status = $labels[$inventory->status];

        return view('inventory.show', [
            'title' => 'Инвентаризация №'.$inventory->id.' ('.$status.')',
            'inventory' => $inventory,
            'items' => $inventory->items,
        ]);
    }

    public function create()
    {
        return view('inventory.create', [
            'title' => 'Новая инвентаризация',
            'shelfs' => Shelf::all(),
        ]);
    }

    public function store(StoreInventoryRequest $request, InventoryService $inventoryService)
    {
        if (! $inventoryService->createInventory($request)) {
            return back()
                ->with('error', 'Ошибка! Инвентаризация не создана');
        }

        return redirect()
            ->route('inventory.inventory_checks')
            ->with('success', 'Инвентаризация создана');
    }

    public function destroy(InventoryCheck $inventory)
    {
        $inventory->delete();

        return redirect()
            ->route('inventory.inventory_checks')
            ->with('success', 'Инвентаризация удалена');
    }
}
