<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Services\MovementMaterialService;

class InventoryController extends Controller
{
    public function byWarehouse()
    {
        $materials = Material::all();

        $materialsQuantity = [];
        foreach ($materials as $material) {
            $inStock = MovementMaterialService::countMaterial($material->id, 1, 3);
            $outStockNew = MovementMaterialService::countMaterial($material->id, 2, 0);
            $outStock = MovementMaterial::query()
                ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
                ->where('material_id', $material->id)
                ->where('orders.type_movement', 2)
                ->whereNotIn('orders.status_movement', [-1])
                ->sum('quantity');

            $materialsQuantity[] = [
                'material' => $material,
                'quantity' => $inStock - $outStock - $outStockNew
            ];
        }

        return view('inventory.warehouse', [
            'title' => 'Материал на складе',
            'materials' => $materialsQuantity,
        ]);
    }

    public function byWorkshop()
    {
        $materials = Material::all();

        $materialsQuantity = [];
        foreach ($materials as $material) {
            $inWorkshop = MovementMaterialService::countMaterial($material->id, 2, 3);
            $outWorkshop = MovementMaterialService::countMaterial($material->id, 3, 3);

            $materialsQuantity[] = [
                'material' => $material,
                'quantity' => $inWorkshop - $outWorkshop
            ];
        }

        return view('inventory.workshop', [
            'title' => 'Материал на производстве',
            'materials' => $materialsQuantity,
        ]);
    }
}
