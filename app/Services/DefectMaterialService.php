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
        $data = [];
        foreach ($request->material_id as $key => $material_id) {
            if ($request->quantity[$key] > 0) {
                $data[] = [
                    'material_id' => $material_id,
                    'quantity' => $request->quantity[$key]
                ];
            }
        }

        try {
            DB::beginTransaction();

            $order = Order::query()->create([
                'storekeeper_id' => auth()->user()->id,
                'type_movement' => 4,
                'status' => 0,
                'comment' => $request->comment,
                'completed_at' => now()
            ]);

            foreach ($data as $item) {
                MovementMaterial::query()->create([
                    'order_id' => $order->id,
                    'material_id' => $item['material_id'],
                    'quantity' => $item['quantity'],
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
