<?php

namespace App\Services;

use App\Http\Requests\SaveCollectMovementMaterialToWorkshopRequest;
use App\Http\Requests\SaveWriteOffMovementMaterialToWorkshopRequest;
use App\Http\Requests\StoreMovementMaterialToWorkshopRequest;
use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MovementMaterialToWorkshopService
{
    /**
     * Получить заказы по статусу с фильтрацией по смене.
     */
    public static function getOrdersByStatus($requestStatus, ?User $user = null)
    {
        $status = [0, 2];

        match ($requestStatus) {
            'all' => $status = [-1, 0, 1, 2, 3],
            'completed' => $status = [3],
            default => $status,
        };

        $query = Order::query()
            ->where('type_movement', 2)
            ->whereIn('status', $status);

        self::applyShiftFilter($query, $user);

        return $query;
    }

    /**
     * Создать заявку на материал для производства (1 заказ, 1 материал).
     */
    public static function store(StoreMovementMaterialToWorkshopRequest $request): bool|RedirectResponse
    {
        $materialId = $request->input('material_id');

        if (empty($materialId)) {
            return back()->withErrors([
                'error' => 'Выберите материал.',
            ]);
        }

        // Проверяем, что материал доступен цеху текущего пользователя
        $workshop = auth()->user()->currentWorkshop();
        if ($workshop) {
            $isAllowed = $workshop->allowedMaterials()
                ->where('materials.id', $materialId)
                ->exists();

            if (! $isAllowed) {
                return back()->withInput()->withErrors([
                    'error' => 'Этот материал недоступен для вашего цеха.',
                ]);
            }
        }

        $shiftId = auth()->user()->currentShift()?->id;

        $exists = MovementMaterial::query()
            ->where('material_id', $materialId)
            ->whereHas('order', function ($query) use ($shiftId) {
                $query->where('type_movement', 2)
                    ->whereIn('status', [0, 2])
                    ->where('shift_id', $shiftId);
            })
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'error' => 'Для вашей смены уже есть незакрытая заявка на этот материал.',
            ]);
        }

        try {
            DB::beginTransaction();

            $field = match (auth()->user()->role->name) {
                'seamstress' => 'seamstress_id',
                'cutter' => 'cutter_id',
                'otk' => 'otk_id',
                default => throw new \Exception('Недопустимая роль: '.auth()->user()->role->name),
            };

            $order = Order::query()->create([
                $field => auth()->user()->id,
                'type_movement' => 2,
                'status' => 0,
                'comment' => $request->comment,
                'shift_id' => auth()->user()->currentShift()?->id,
                'workshop_id' => auth()->user()->currentWorkshop()?->id,
            ]);

            MovementMaterial::query()->create([
                'order_id' => $order->id,
                'material_id' => $materialId,
                'ordered_quantity' => 0,
            ]);

            $material = Material::find($materialId);
            $text = auth()->user()->name.' запросил(а) материал: '.$material->title;

            Log::channel('tg')
                ->notice('Отправляем сообщение в ТГ админу и работающим кладовщикам: '.$text);

            TgService::sendMessage(config('telegram.admin_id'), $text);
            MaxService::sendMessage(config('services.max.admin_id'), $text);

            foreach (UserService::getListStorekeepersWorkingToday() as $tgId) {
                TgService::sendMessage($tgId, $text);
            }

            DB::commit();
        } catch (Throwable $e) {
            Log::channel('materials')
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
                    'status' => Roll::STATUS_SHIPPED_TO_WORKSHOP,
                    'shift_id' => $order->shift_id,
                ]);

                $movementMaterial = MovementMaterial::query()
                    ->find($movementMaterialId);

                $list .= '• '.$movementMaterial->material->title.' '.$movementMaterial->quantity.' '.$movementMaterial->material->unit."\n";
            }

            $text = 'Кладовщик '.auth()->user()->name.' отгрузил материал на производство: '."\n".$list;

            Log::channel('tg')
                ->notice('Отправляем сообщение в ТГ админу и работающим швеям: '.$text);

            TgService::sendMessage(config('telegram.admin_id'), $text);
            MaxService::sendMessage(config('services.max.admin_id'), $text);

            $workshopId = $order->shift?->workshop_id;
            foreach (UserService::getListSeamstressesWorkingToday($workshopId) as $tgId) {
                TgService::sendMessage($tgId, $text);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::channel('materials')
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

    /**
     * Количество не отгруженных поставок с учётом смены или цеха.
     */
    public static function getCountNotShippedMovements(?User $user = null, ?int $workshopId = null): int
    {
        $query = Order::query()
            ->where('type_movement', 2)
            ->where('status', 0);

        self::applyShiftFilter($query, $user, $workshopId);

        return $query->count();
    }

    /**
     * Количество непринятых заказов с учётом смены или цеха.
     */
    public static function getCountNotReceivedMovements(?User $user = null, ?int $workshopId = null): int
    {
        $query = Order::query()
            ->where('type_movement', 2)
            ->where('status', 2);

        self::applyShiftFilter($query, $user, $workshopId);

        return $query->count();
    }

    /**
     * Применить фильтр по смене или цеху для швей, закройщиков и ОТК.
     */
    private static function applyShiftFilter($query, ?User $user = null, ?int $workshopId = null)
    {
        // Фильтр по цеху — приоритетнее фильтра по смене
        if ($workshopId) {
            $query->whereHas('shift', fn ($q) => $q->where('workshop_id', $workshopId));

            return $query;
        }

        if ($user && in_array($user->role?->name, ShiftService::SHIFT_ROLES)) {
            $userShift = $user->currentShift();
            if ($userShift) {
                $query->where('shift_id', $userShift->id);
            }
        }

        return $query;
    }

    /**
     * Количество товаров на стикеровке (статус 5) с опциональной фильтрацией по цеху.
     */
    public static function getStickeredMarketplaceOrderItem(?int $workshopId = null): int
    {
        return MarketplaceOrderItem::query()
            ->where('status', 5)
            ->when($workshopId, fn ($q) => $q->where('workshop_id', $workshopId))
            ->count();
    }
}
