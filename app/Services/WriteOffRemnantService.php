<?php

namespace App\Services;

use App\Http\Requests\StoreRemnantsRequest;
use App\Models\MovementMaterial;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WriteOffRemnantService
{
    public static function store(StoreRemnantsRequest $request): RedirectResponse
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

            $list = '';
            foreach ($materialIds as $key => $material_id) {

                if($material_id == 0) {
                    continue;
                }

                $maxQuantity = InventoryService::remnantsMaterialInWarehouse($material_id);

                if ((float)$quantities[$key] > $maxQuantity) {
                    DB::rollBack();
                    return back()->withErrors([
                        'error' => 'Невозможно списать больше материала, чем есть в наличии.'
                    ]);
                }

                $movementMaterial = MovementMaterial::query()->create([
                    'order_id' => $order->id,
                    'material_id' => $material_id,
                    'quantity' => $quantities[$key],
                ]);

                $list .= '• ' . $movementMaterial->material->title . ' '
                    . $movementMaterial->quantity . ' '
                    . $movementMaterial->material->unit . ' '
                    . "\n";
            }

            Log::channel('erp')
                ->notice('   Кладовщик ' . auth()->user()->name .
                    ' создал новое списание остатков:' . "\n"  . $list);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect()
            ->route('write_off_remnants.index')
            ->with('success', 'Поступление добавлено');
    }
}
