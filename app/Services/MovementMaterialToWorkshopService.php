<?php

namespace App\Services;

use App\Http\Requests\SaveCollectMovementMaterialToWorkshopRequest;
use App\Http\Requests\SaveWriteOffMovementMaterialToWorkshopRequest;
use App\Http\Requests\StoreMovementMaterialToWorkshopRequest;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MovementMaterialToWorkshopService
{
    public static function getOrdersByStatus($requestStatus)
    {
        $status = [0, 2];

        match ($requestStatus) {
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
        $quantity = $request->input('quantity');

        if (empty($materialIds) || ($quantity < 1 || $quantity > 10)) {
            return back()->withErrors([
                'error' => 'Заполните правильно список материалов и количество.',
            ]);
        }

        try {
            DB::beginTransaction();

            $field = match (auth()->user()->role->name) {
                'seamstress' => 'seamstress_id',
                'cutter' => 'cutter_id',
                default => throw new \Exception('Недопустимая роль: '.auth()->user()->role->name),
            };

            $material = '';

            for ($i = 0; $i < $quantity; $i++) {

                $order = Order::query()->create([
                    $field => auth()->user()->id,
                    'type_movement' => 2,
                    'status' => 0,
                    'comment' => $request->comment,
                ]);

                foreach ($materialIds as $key => $material_id) {
                    if ($material_id == 0) {
                        continue;
                    }

                    $movementMaterial = MovementMaterial::query()->create([
                        'order_id' => $order->id,
                        'material_id' => $material_id,
                        'ordered_quantity' => 0,
                    ]);

                    $material = '• '.$movementMaterial->material->title;
                }
            }

            $text = 'Швея '.auth()->user()->name.' запросила: '."\n".$material.' x '.$quantity;

            Log::channel('erp')
                ->notice('Отправляем сообщение в ТГ админу и работающим кладовщикам: '.$text);

            TgService::sendMessage(config('telegram.admin_id'), $text);

            foreach (UserService::getListStorekeepersWorkingToday() as $tgId) {
                TgService::sendMessage($tgId, $text);
            }

            DB::commit();
        } catch (Throwable $e) {
            Log::channel('erp')
                ->error($e->getMessage());

            DB::rollBack();

            return false;
        }

        return true;
    }

    public static function save_collect(SaveCollectMovementMaterialToWorkshopRequest $request, Order $order): bool|RedirectResponse
    {
        $movementMaterialIds = $request->input('id', []);
        $rollCodes = $request->input('roll_code', []);

        if (
            empty($movementMaterialIds)
        ) {
            return back()->withErrors([
                'error' => 'Заполните правильно список материалов и количество.',
            ]);
        }

        try {
            DB::beginTransaction();

            $order->update([
                'status' => 2,
                'storekeeper_id' => auth()->user()->id,
            ]);

            $list = '';
            foreach ($movementMaterialIds as $key => $movementMaterialId) {
                $roll = Roll::where('roll_code', $rollCodes[$key])->first();
                MovementMaterial::query()
                    ->where('id', $movementMaterialId)
                    ->update([
                        'quantity' => $roll->initial_quantity,
                        'roll_id' => $roll->id,
                    ]);

                $roll->update([
                    'status' => Roll::STATUS_IN_WORKSHOP,
                ]);

                $movementMaterial = MovementMaterial::query()
                    ->find($movementMaterialId);

                $list .= '• '.$movementMaterial->material->title.' '.$movementMaterial->quantity.' '.$movementMaterial->material->unit."\n";
            }

            $text = 'Кладовщик '.auth()->user()->name.' отгрузил материал на производство: '."\n".$list;

            Log::channel('erp')
                ->notice('Отправляем сообщение в ТГ админу и работающим швеям: '.$text);

            TgService::sendMessage(config('telegram.admin_id'), $text);

            foreach (UserService::getListSeamstressesWorkingToday() as $tgId) {
                TgService::sendMessage($tgId, $text);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::channel('erp')
                ->error('Ошибка при сохранении отгрузки материала: '.$e->getMessage());

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
                'error' => 'Заполните правильно список материалов и количество.',
            ]);
        }

        try {
            DB::beginTransaction();

            $order = Order::query()->create([
                'type_movement' => 6,
                'status' => 3,
                'is_approved' => 1,
                'comment' => $request->comment,
                'completed_at' => now(),
            ]);

            foreach ($movementMaterialIds as $key => $material_id) {

                if ($material_id == 0) {
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
