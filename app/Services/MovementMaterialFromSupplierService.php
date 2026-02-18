<?php

namespace App\Services;

use App\Http\Requests\StoreMovementMaterialFromSupplierRequest;
use App\Http\Requests\UpdateMovementMaterialFromSupplierRequest;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MovementMaterialFromSupplierService
{
    public static function store(StoreMovementMaterialFromSupplierRequest $request): bool|RedirectResponse
    {
        $quantities = $request->input('quantity', []);
        $numberRolls = $request->input('number_rolls', []);
        $materialId = $request->input('material_id');

        if (empty($quantities)) {
            return back()->withErrors([
                'error' => 'Заполните правильно количество.',
            ]);
        }

        try {
            DB::beginTransaction();

            $order = Order::query()->create([
                'supplier_id' => $request->supplier_id,
                'storekeeper_id' => auth()->user()->id,
                'type_movement' => 1,
                'status' => 0,
                'comment' => $request->comment,
                'completed_at' => now(),
            ]);

            $list = '';
            $movementMaterial = null;
            $material = Material::find($materialId);

            foreach ($quantities as $key => $quantity) {
                $numberRoll = $numberRolls[$key] ?? 1;

                if ($quantity == 0) {
                    continue;
                }

                for ($i = 0; $i < $numberRoll; $i++) {
                    $roll = Roll::query()->create([
                        'material_id' => $materialId,
                        'status' => Roll::STATUS_IN_STORAGE,
                        'initial_quantity' => $quantity,
                    ]);

                    $roll->roll_code = $material->type_id.'-'.str_pad($roll->id, 6, '0', STR_PAD_LEFT);
                    $roll->save();

                    $movementMaterial = MovementMaterial::query()->create([
                        'order_id' => $order->id,
                        'material_id' => $materialId,
                        'quantity' => $quantity,
                        'roll_id' => $roll->id,
                    ]);
                }

                if ($movementMaterial !== null) {
                    $list .= '• '.$movementMaterial->material->title.' '
                        .$movementMaterial->quantity.' '
                        .$movementMaterial->material->unit."\n";
                }
            }

            Log::channel('erp')
                ->notice('   Кладовщик '.auth()->user()->name.
                    ' добавил поступление материала на склад от поставщика '
                    .$order->supplier->title.' :'."\n".$list);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::channel('erp')
                ->error('   Кладовщик '.auth()->user()->name.
                    ' не добавил поступление материала на склад от поставщика : '
                    .$e->getMessage());

            return false;
        }

        return true;
    }

    public static function update(UpdateMovementMaterialFromSupplierRequest $request, Order $order): bool|RedirectResponse
    {
        $materialIds = $request->input('id', []);
        $prices = $request->input('price', []);

        if (auth()->user()->isAdmin()) {
            $order->supplier_id = $request->supplier_id;
            $order->save();
        }

        if (
            empty($materialIds) || empty($prices)
        ) {
            return back()->withErrors([
                'error' => 'Заполните правильно материалы и цены.',
            ]);
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 3,
            ]);

            $list = '';
            foreach ($materialIds as $key => $material_id) {
                MovementMaterial::query()
                    ->where('id', $material_id)
                    ->update([
                        'price' => $prices[$key],
                    ]);

                $movementMaterial = MovementMaterial::query()->find($material_id);

                $list .= '• '.$movementMaterial->material->title.' '
                    .$movementMaterial->quantity.' '
                    .$movementMaterial->material->unit.' цена: '
                    .$movementMaterial->price.' '
                    ."\n";
            }

            Log::channel('erp')
                ->notice('   Админ '.auth()->user()->name.
                    ' одобрил поступление материала на склад от поставщика '
                    .$order->supplier->title.' :'."\n".$list);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return false;
        }

        return true;
    }
}
