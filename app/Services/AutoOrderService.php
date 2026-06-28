<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use App\Models\Shift;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoOrderService
{
    /**
     * Порог остатка в цехе, при котором создаётся автозаказ.
     */
    private const THRESHOLD = 100;

    /**
     * Проверить все активные смены и создать автозаказы для материалов ниже порога.
     *
     * Логика:
     * 1. Получаем все активные смены, группируем по цеху
     * 2. Для каждого цеха — получаем только привязанные активные материалы
     * 3. Считаем остатки рулонов в цехе (status=in_workshop) по material_id + shift_id
     * 4. Материалы с остатком <= THRESHOLD или без рулонов в цехе вовсе -> автозаказ
     *
     * @return int[] Массив ID созданных заказов.
     */
    public static function checkAndCreateAutoOrders(): array
    {
        $shifts = Shift::query()->active()->get();

        if ($shifts->isEmpty()) {
            return [];
        }

        $workshopQuantities = self::getWorkshopQuantities($shifts);

        $createdOrderIds = [];

        // Группируем смены по цеху — материалы загружаются один раз на цех
        $shiftsByWorkshop = $shifts->groupBy('workshop_id');

        foreach ($shiftsByWorkshop as $workshopId => $workshopShifts) {
            $workshop = Workshop::find($workshopId);

            if (! $workshop) {
                continue;
            }

            $materials = $workshop->allowedMaterials()
                ->where('is_active', true)
                ->get();

            foreach ($workshopShifts as $shift) {
                foreach ($materials as $material) {
                    $key = $material->id.'_'.$shift->id;
                    $quantity = $workshopQuantities[$key] ?? 0;

                    if ($quantity > self::THRESHOLD) {
                        continue;
                    }

                    $orderId = self::createAutoOrder($material, $shift);

                    if ($orderId) {
                        $createdOrderIds[] = $orderId;
                    }
                }
            }
        }

        return $createdOrderIds;
    }

    /**
     * Получить остатки материалов в цехе, сгруппированные по material_id и shift_id.
     *
     * Возвращает массив вида: [materialId_shiftId => float quantity]
     * Учитывает только рулоны со статусом in_workshop и вычитает списанное количество.
     *
     * @return array<string, float>
     */
    private static function getWorkshopQuantities($shifts): array
    {
        $usedSub = MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->whereIn('orders.type_movement', [3, 4, 6, 7])
            ->select('movement_materials.roll_id', DB::raw('SUM(movement_materials.quantity) as total_used'))
            ->groupBy('movement_materials.roll_id');

        $rollData = Roll::query()
            ->where('rolls.status', Roll::STATUS_IN_WORKSHOP)
            ->whereIn('rolls.shift_id', $shifts->pluck('id'))
            ->leftJoinSub($usedSub, 'used', 'used.roll_id', '=', 'rolls.id')
            ->select(
                'rolls.material_id',
                'rolls.shift_id',
                DB::raw('SUM(rolls.initial_quantity - COALESCE(used.total_used, 0)) as total_quantity')
            )
            ->groupBy('rolls.material_id', 'rolls.shift_id')
            ->get();

        $quantities = [];
        foreach ($rollData as $row) {
            $key = $row->material_id.'_'.$row->shift_id;
            $quantities[$key] = round($row->total_quantity, 2);
        }

        return $quantities;
    }

    /**
     * Создать автоматический заказ материала для конкретной смены.
     *
     * Проверяет отсутствие дубликата (открытой заявки type_movement=2, status in [0,2]
     * для этого материала в этой смене). Если дубля нет — создаёт заказ и отправляет уведомления.
     *
     * @return int|null ID созданного заказа или null если заказ не создан.
     */
    public static function createAutoOrder(Material $material, Shift $shift): ?int
    {
        $exists = MovementMaterial::query()
            ->where('material_id', $material->id)
            ->whereHas('order', function ($query) use ($shift) {
                $query->where('type_movement', 2)
                    ->whereIn('status', [0, 2])
                    ->where('shift_id', $shift->id);
            })
            ->exists();

        if ($exists) {
            return null;
        }

        try {
            $order = DB::transaction(function () use ($material, $shift) {
                $order = Order::query()->create([
                    'type_movement' => 2,
                    'status' => 0,
                    'comment' => '[Автозаказ]',
                    'shift_id' => $shift->id,
                ]);

                MovementMaterial::query()->create([
                    'order_id' => $order->id,
                    'material_id' => $material->id,
                    'ordered_quantity' => 0,
                ]);

                return $order;
            });

            $text = '[Автозаказ] Смена "'.$shift->name.'" — мало материала: '.$material->title;

            Log::channel('materials')->info($text);

            TgService::sendMessage(config('telegram.admin_id'), $text);
            MaxService::sendMessage(config('services.max.admin_id'), $text);

            foreach (UserService::getListStorekeepersWorkingToday() as $tgId) {
                TgService::sendMessage($tgId, $text);
            }

            return $order->id;
        } catch (Throwable $e) {
            Log::channel('materials')->error('Ошибка автозаказа: '.$e->getMessage());

            return null;
        }
    }
}
