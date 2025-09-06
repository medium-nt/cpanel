<?php

namespace App\Services;

use App\Http\Requests\SaveCollectMovementMaterialToWorkshopRequest;
use App\Http\Requests\SaveWriteOffMovementMaterialToWorkshopRequest;
use App\Http\Requests\StoreMovementMaterialToWorkshopRequest;
use App\Models\MovementMaterial;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MovementMaterialToWorkshopService
{
    public static function getOrdersByStatus($requestStatus)
    {
        $status = [0, 2];

        match ($requestStatus)
        {
            'all' => $status = [-1, 0, 1, 2, 3],
            default => $status,
        };

        return Order::query()
            ->where('type_movement', 2)
            ->whereIn('status', $status);
    }

    public static function store(StoreMovementMaterialToWorkshopRequest $request): bool|RedirectResponse
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

            $field = match (auth()->user()->role->name) {
                'seamstress' => 'seamstress_id',
                'cutter'     => 'cutter_id',
                default      => throw new \Exception('Недопустимая роль: ' . auth()->user()->role->name),
            };

            $order = Order::query()->create([
                $field => auth()->user()->id,
                'type_movement' => 2,
                'status' => 0,
                'comment' => $request->comment
            ]);

            $list = '';

            foreach ($materialIds as $key => $material_id) {
                if($material_id == 0) {
                    continue;
                }

                $movementData['order_id'] = $order->id;
                $movementData['material_id'] = $material_id;
                $movementData['ordered_quantity'] = $quantities[$key];

                $movementMaterial = MovementMaterial::query()->create($movementData);

                $list .= '• ' . $movementMaterial->material->title . ' ' . $movementMaterial->ordered_quantity . ' ' . $movementMaterial->material->unit . "\n";
            }

            $text = 'Швея ' . auth()->user()->name . ' запросила: ' . "\n"  . $list;

            Log::channel('erp')
                ->notice('    Отправляем сообщение в ТГ админу и работающим кладовщикам: ' . $text);

            TgService::sendMessage(config('telegram.admin_id'), $text);

            foreach (UserService::getListStorekeepersWorkingToday() as $tgId) {
                TgService::sendMessage($tgId, $text);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return false;
        }

        return true;
    }

    public static function save_collect(SaveCollectMovementMaterialToWorkshopRequest $request, Order $order): bool|RedirectResponse
    {
        $movementMaterialIds = $request->input('id', []);
        $quantities = $request->input('quantity', []);

        if (
            empty($movementMaterialIds) || empty($quantities)
        ) {
            return back()->withErrors([
                'error' => 'Заполните правильно список материалов и количество.'
            ]);
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 2,
                'storekeeper_id' => auth()->user()->id
            ]);

            $list = '';
            foreach ($movementMaterialIds as $key => $movementMaterialId) {
                MovementMaterial::query()
                    ->where('id', $movementMaterialId)
                    ->update([
                        'quantity' => $quantities[$key],
                    ]);

                $movementMaterial = MovementMaterial::query()
                    ->find($movementMaterialId);

                $list .= '• ' . $movementMaterial->material->title . ' ' . $movementMaterial->quantity . ' ' . $movementMaterial->material->unit . "\n";
            }

            $text = 'Кладовщик ' . auth()->user()->name . ' отгрузил материал на производство: ' . "\n"  . $list;

            Log::channel('erp')
                ->notice('    Отправляем сообщение в ТГ админу и работающим швеям: ' . $text);

            TgService::sendMessage(config('telegram.admin_id'), $text);

            foreach (UserService::getListSeamstressesWorkingToday() as $tgId) {
                TgService::sendMessage($tgId, $text);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return false;
        }

        return true;
    }

    public static function save_write_off(SaveWriteOffMovementMaterialToWorkshopRequest $request): bool|RedirectResponse
    {
        $movementMaterialIds = $request->input('material_id', []);
        $quantities = $request->input('ordered_quantity', []);

        if (
            empty($movementMaterialIds) || empty($quantities)
        ) {
            return back()->withErrors([
                'error' => 'Заполните правильно список материалов и количество.'
            ]);
        }

        try {
            DB::beginTransaction();

            $order = Order::query()->create([
                'type_movement' => 6,
                'status' => 3,
                'is_approved' => 1,
                'comment' => $request->comment,
                'completed_at' => now()
            ]);

            foreach ($movementMaterialIds as $key => $material_id) {

                if($material_id == 0) {
                    continue;
                }

                $movementData['order_id'] = $order->id;
                $movementData['material_id'] = $material_id;
                $movementData['quantity'] = $quantities[$key];

                MovementMaterial::query()->create($movementData);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return false;
        }

        return true;
    }

    public static function getCountNotShippedMovements(): int
    {
        return Order::query()
            ->where('type_movement', 2)
            ->where('status', 0)
            ->count();
    }

    public static function getCountNotReceivedMovements(): int
    {
        return Order::query()
            ->where('type_movement', 2)
            ->where('status', 2)
            ->count();
    }
}
