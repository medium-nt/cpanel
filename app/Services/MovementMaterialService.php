<?php

namespace App\Services;

use App\Models\MovementMaterial;

class MovementMaterialService
{
    public static function countMaterial($materialId, $type, $status): int
    {
        return MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->where('material_id', $materialId)
            ->where('orders.type_movement', $type)
            ->where('orders.status_movement', $status)
            ->sum('quantity');
    }
}
