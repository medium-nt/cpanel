<?php

namespace App\Services;

use App\Http\Requests\StoreMovementMaterialFromSupplierRequest;
use App\Http\Requests\UpdateMovementMaterialFromSupplierRequest;
use App\Models\MovementMaterial;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MovementMaterialFromSupplierService
{
    public static function store(StoreMovementMaterialFromSupplierRequest $request): bool|RedirectResponse
    {
        $materialIds = $request->input('material_id', []);
        $quantities = $request->input('quantity', []);

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
                'supplier_id' => $request->supplier_id,
                'storekeeper_id' => auth()->user()->id,
                'type_movement' => 1,
                'status' => 0,
                'comment' => $request->comment,
                'completed_at' => now()
            ]);

            $list = '';
            foreach ($materialIds as $key => $material_id) {
                $movementMaterial = MovementMaterial::query()->create([
                    'order_id' => $order->id,
                    'material_id' => $material_id,
                    'quantity' => $quantities[$key],
                ]);

                $list .= '• ' . $movementMaterial->material->title . ' '
                    . $movementMaterial->quantity . ' '
                    . $movementMaterial->material->unit . "\n";
            }

            Log::channel('erp')
                ->notice('   Кладовщик ' . auth()->user()->name .
                    ' добавил поступление материала на склад от поставщика '
                    . $order->supplier->title .' :' . "\n"  . $list);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return false;
        }

        return true;
    }

    public static function update(UpdateMovementMaterialFromSupplierRequest $request, Order $order): bool|RedirectResponse
    {
        $materialIds = $request->input('id', []);
        $prices = $request->input('price', []);

        if (
            empty($materialIds) || empty($prices)
        ) {
            return back()->withErrors([
                'error' => 'Заполните правильно материалы и цены.'
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

                $list .= '• ' . $movementMaterial->material->title . ' '
                    . $movementMaterial->quantity . ' '
                    . $movementMaterial->material->unit . ' цена: '
                    . $movementMaterial->price . ' '
                    . "\n";
            }

            Log::channel('erp')
                ->notice('   Админ ' . auth()->user()->name .
                    ' одобрил поступление материала на склад от поставщика '
                    . $order->supplier->title .' :' . "\n"  . $list);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return false;
        }

        return true;
    }
}
