<?php

namespace App\Services;

use App\Models\MovementMaterial;
use App\Models\Roll;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RollService
{
    /**
     * Порог остатка (в единицах материала), ниже которого рулон считается «заканчивающимся».
     */
    public const LOW_MATERIAL_THRESHOLD = 5;

    /**
     * Возвращает базовый Builder для незакрытых рулонов с малым остатком.
     * Можно дальше цеплять ->with(), ->orderBy(), ->paginate() и т.д.
     */
    public static function lowMaterialRollsQuery(?int $shiftId = null): Builder
    {
        $usedSub = MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->whereIn('orders.type_movement', [3, 4])
            ->groupBy('movement_materials.roll_id')
            ->select('movement_materials.roll_id', DB::raw('SUM(movement_materials.quantity) as total_used'));

        $query = Roll::query()
            ->leftJoinSub($usedSub, 'used', 'rolls.id', '=', 'used.roll_id')
            ->where('rolls.status', Roll::STATUS_IN_WORKSHOP)
            ->whereRaw('(rolls.initial_quantity - COALESCE(used.total_used, 0)) <= ?', [self::LOW_MATERIAL_THRESHOLD])
            ->select('rolls.*', DB::raw('(rolls.initial_quantity - COALESCE(used.total_used, 0)) as computed_quantity'))
            ->with('material');

        if ($shiftId !== null) {
            $query->where('rolls.shift_id', $shiftId);
        }

        return $query;
    }

    /**
     * Возвращает незакрытые рулоны (status = in_workshop), остаток материала в которых
     * меньше или равен пороговому значению.
     *
     * @param  int|null  $shiftId  Если передан — фильтрует по смене.
     * @return Collection<Roll>
     */
    public static function getLowMaterialRolls(?int $shiftId = null): Collection
    {
        return self::lowMaterialRollsQuery($shiftId)
            ->orderBy('computed_quantity', 'asc')
            ->get();
    }

    /**
     * Возвращает количество незакрытых рулонов с малым остатком.
     */
    public static function getLowMaterialRollsCount(?int $shiftId = null): int
    {
        return self::lowMaterialRollsQuery($shiftId)->count();
    }
}
