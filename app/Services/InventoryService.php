<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MovementMaterial;

class InventoryService
{
    public static function materialInWorkhouse($materialId): float
    {
        $inWorkshop = self::countMaterial($materialId, 2, 3);
        $outWorkshop = self::countMaterial($materialId, 3, 3);
        $holdWorkshop = self::countMaterial($materialId, 3, 4);

        return $inWorkshop - $outWorkshop - $holdWorkshop;
    }

    public static function materialInWarehouse($materialId): float
    {
        $inStock = self::countMaterial($materialId, 1, 3);
        $outStockNew = self::countMaterial($materialId, 2, 0);

        $outStock = MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->where('material_id', $materialId)
            ->where('orders.type_movement', 2)
            ->whereNotIn('orders.status', [-1])
            ->sum('quantity');

        return $inStock - $outStock - $outStockNew;
    }

    public static function countMaterial($materialId, $type, $status): float
    {
        return MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->where('material_id', $materialId)
            ->where('orders.type_movement', $type)
            ->where('orders.status', $status)
            ->sum('quantity');
    }

    public static function materialsQuantityBy(string $type): array
    {
        $materials = Material::all();

        $materialsQuantity = [];
        foreach ($materials as $material) {
            match ($type) {
                'warehouse' => $quantity = self::materialInWarehouse($material->id),
                'workhouse' => $quantity = self::materialInWorkhouse($material->id),
            };

            $materialsQuantity[] = [
                'material' => $material,
                'quantity' => $quantity,
            ];
        }

        return $materialsQuantity;
    }

}
