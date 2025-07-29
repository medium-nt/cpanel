<?php

namespace App\Services;

use App\Http\Requests\SaveDefectMaterialRequest;
use App\Models\MovementMaterial;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DefectMaterialService
{
    public static function save(Request $request, Order $order): false|array
    {
        if ($request->status == 3) {

            $movementMaterial = $order->movementMaterials()->first();

            $typeName = match ($order->type_movement) {
                4 => 'брак',
                7 => 'остаток',
                default => '---',
            };

            $list = '• ' . $movementMaterial->material->title . ' ' . $movementMaterial->quantity . ' ' . $movementMaterial->material->unit . "\n";

            $text = 'Кладовщик ' . auth()->user()->name . ' забрал ' . $typeName . ' с производства:' . "\n"  . $list;

            Log::channel('erp')
                ->notice('    Отправляем сообщение в ТГ админу и работающим швеям: ' . $text);

            TgService::sendMessage(config('telegram.admin_id'), $text);

            foreach (UserService::getListSeamstressesWorkingToday() as $tgId) {
                TgService::sendMessage($tgId, $text);
            }
        }

        return match($request->status) {
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
    }

    public static function store(SaveDefectMaterialRequest $request): bool
    {
        $movementMaterialIds = $request->input('material_id', []);
        $quantities = $request->input('ordered_quantity', []);

        try {
            DB::beginTransaction();

            $order = Order::query()->create([
                'seamstress_id' => auth()->user()->id,
                'type_movement' => $request->type_movement_id,
                'status' => 0,
                'comment' => $request->comment,
                'completed_at' => now()
            ]);

            $list = $typeName = '';

            foreach ($movementMaterialIds as $key => $material_id) {
                if($request->type_movement_id == 7 && $quantities[$key] > 1) {
                    DB::rollBack();
                    return false;
                }

                $movementMaterial = MovementMaterial::query()->create([
                    'order_id' => $order->id,
                    'material_id' => $material_id,
                    'quantity' => $quantities[$key],
                ]);

                $typeName = match ($request->type_movement_id) {
                    '4' => 'брак',
                    '7' => 'остаток',
                    default => '---',
                };

                $list .= '• ' . $movementMaterial->material->title . ' ' . $movementMaterial->quantity . ' ' . $movementMaterial->material->unit . "\n";
            }

            $text = 'Швея ' . auth()->user()->name . ' указала ' . $typeName . ': ' . "\n"  . $list;

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

    public static function delete(Order $order): array
    {
        if ($order->status != 1) {
            return [
                'success' => false,
                'message' => 'Заказ уже забран на склад!'
            ];
        }

        try {
            DB::beginTransaction();

            MovementMaterial::query()
                ->where('order_id', $order->id)
                ->delete();

            $order->delete();

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Внутренняя ошибка'
            ];
        }

        return [
            'success' => true,
            'message' => 'Заказ на брак удален'
        ];
    }
}
