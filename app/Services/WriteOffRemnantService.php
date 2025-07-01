<?php

namespace App\Services;

use App\Http\Requests\StoreRemnantsRequest;
use App\Models\MovementMaterial;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class WriteOffRemnantService
{
    public static function store(StoreRemnantsRequest $request): bool|RedirectResponse
    {
        $materialIds = $request->input('material_id', []);
        $quantities = $request->input('ordered_quantity', []);

        if (
            empty($materialIds) || empty($quantities)
        ) {
            return back()->withErrors([
                'error' => 'Заполните правильно список материалов и количество.'
            ]);
        }

        try {
            DB::beginTransaction();

            $order = Order::query()->create([
                'storekeeper_id' => auth()->user()->id,
                'type_movement' => 8,
                'status' => 3,
                'comment' => $request->comment,
                'completed_at' => now()
            ]);

            foreach ($materialIds as $key => $material_id) {
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
