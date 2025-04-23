<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MovementMaterial;

class InventoryService
{
    public static function materialInWorkshop($materialId): float
    {
        $inWorkshop = self::countMaterial($materialId, 2, 3);
        $holdWorkshopToItems = self::countMaterial($materialId, 3, 4);
        $outWorkshopToItems = self::countMaterial($materialId, 3, 3);
        $outToDefectMaterials = self::countMaterial($materialId, 4, 3);
        $writeOff = self::countMaterial($materialId, 6, 3);

        $result = $inWorkshop - $holdWorkshopToItems - $outWorkshopToItems - $outToDefectMaterials - $writeOff;

        return round($result, 2);
    }

    public static function materialInWarehouse($materialId): float
    {
        $inStock = self::countMaterial($materialId, 1, 3);
        $holdOutStockNew = self::countMaterial($materialId, 2, 0);

        $outStock = MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->where('material_id', $materialId)
            ->where('orders.type_movement', 2)
            ->whereNotIn('orders.status', [-1])
            ->sum('quantity');

        $result = $inStock - $outStock - $holdOutStockNew;

        return round($result, 2);
    }

    public static function defectMaterialInWarehouse($materialId): float
    {
        $inStock = self::countMaterial($materialId, 4, 3);
        $outStockNew = self::countMaterial($materialId, 5, 3);

        $result = $inStock - $outStockNew;
        return round($result, 2);
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
                'workhouse' => $quantity = self::materialInWorkshop($material->id),
            };

            $materialsQuantity[] = [
                'material' => $material,
                'quantity' => $quantity,
            ];
        }

        return $materialsQuantity;
    }

}
