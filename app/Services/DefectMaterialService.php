<?php

namespace App\Services;

use App\Http\Requests\SaveDefectMaterialRequest;
use App\Models\MovementMaterial;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class DefectMaterialService
{
    public static function save(Request $request): false|array
    {
        $array = match ($request->status) {
            '-1' => [
                'status' => 'error',
                'text' => 'отменен',
            ],
            '1' => [
                'status' => 'success',
                'text' => 'одобрен',
            ],
            '3' => [
                'status' => 'success',
                'text' => 'принят на складе',
            ],
            default => false,
        };

        return $array;
    }

    public static function store(SaveDefectMaterialRequest $request): bool
    {
        $movementMaterialIds = $request->input('material_id', []);
        $quantities = $request->input('ordered_quantity', []);

        try {
            DB::beginTransaction();

            $order = Order::query()->create([
                'seamstress_id' => auth()->user()->id,
                'type_movement' => 4,
                'status' => 0,
                'comment' => $request->comment,
                'completed_at' => now()
            ]);

            foreach ($movementMaterialIds as $key => $material_id) {
                MovementMaterial::query()->create([
                    'order_id' => $order->id,
                    'material_id' => $material_id,
                    'quantity' => $quantities[$key],
                ]);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return false;
        }

        return true;

    }
}
