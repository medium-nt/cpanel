<?php

namespace App\Services;

use App\Http\Requests\StoreDefectMaterialToSupplierRequest;
use App\Http\Requests\UpdateMovementMaterialFromSupplierRequest;
use App\Models\MovementMaterial;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class MovementDefectMaterialToSupplierService
{
    public static function store(StoreDefectMaterialToSupplierRequest $request): RedirectResponse
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
                'supplier_id' => $request->supplier_id,
                'storekeeper_id' => auth()->user()->id,
                'type_movement' => 5,
                'status' => 3,
                'comment' => $request->comment,
                'completed_at' => now()
            ]);

            foreach ($materialIds as $key => $material_id) {

                $maxQuantity = InventoryService::defectMaterialInWarehouse($material_id);

                if ((float)$quantities[$key] > $maxQuantity) {
                    DB::rollBack();
                    return back()->withErrors([
                        'error' => 'Невозможно списать больше материала, чем есть в наличии.'
                    ]);
                }

                MovementMaterial::query()->create([
                    'order_id' => $order->id,
                    'material_id' => $material_id,
                    'quantity' => $quantities[$key],
                ]);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect()
            ->route('movements_defect_to_supplier.index')
            ->with('success', 'Поступление добавлено');
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

            foreach ($materialIds as $key => $material_id) {
                MovementMaterial::query()
                    ->where('id', $material_id)
                    ->update([
                        'price' => $prices[$key],
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
