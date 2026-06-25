<?php

namespace App\Services;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrderItem;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;

class KioskService
{
    /**
     * Проверяет, есть ли у пользователя незавершённые заказы в работе.
     */
    public function hasOrdersInWork(User $user): bool
    {
        if ($user->isSeamstress()) {
            return MarketplaceOrderItem::query()
                ->where('seamstress_id', $user->id)
                ->where('status', 4)
                ->exists();
        }

        if ($user->isCutter()) {
            return MarketplaceOrderItem::query()
                ->where('cutter_id', $user->id)
                ->where('status', 7)
                ->exists();
        }

        return false;
    }

    /**
     * Фильтрует список расходов по типу упаковочного материала (флаер/пакет).
     */
    public function filterConsumptionsByMaterialUsed($consumptions, string $materialUsed): Collection
    {
        $keywords = match ($materialUsed) {
            'flyer' => ['флаер'],
            'bag' => ['пакет'],
            'flyer-bag' => ['флаер', 'пакет'],
            default => [],
        };

        return $consumptions->filter(function ($consumption) use ($keywords) {
            if (empty($keywords)) {
                return false;
            }
            $title = mb_strtolower($consumption->material->title);
            foreach ($keywords as $keyword) {
                if (str_contains($title, $keyword)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Списывает упаковочные материалы (флаер/пакет) для товара, создавая заказ на расход
     * с привязкой к рулонам текущей смены.
     *
     * @throws RuntimeException если рулон нужного материала отсутствует в цехе
     */
    public function deductPackagingMaterials(MarketplaceItem $item, string $materialUsed, string $comment, Shift $shift): void
    {
        $consumptions = $item->consumption()->with('material')->get();
        $filteredConsumptions = $this->filterConsumptionsByMaterialUsed($consumptions, $materialUsed);

        // Сначала проверяем наличие всех рулонов — чтобы не создавать частичный расход
        $rolls = [];
        foreach ($filteredConsumptions as $consumption) {
            $roll = $this->findPackagingRoll($consumption->material_id, $shift);
            if (! $roll) {
                throw new RuntimeException('Нет рулона для "'.$consumption->material->title.'" в цехе');
            }
            $rolls[$consumption->material_id] = $roll;
        }

        $order = Order::query()->create([
            'type_movement' => 3,
            'status' => 3,
            'shift_id' => $shift->id,
            'workshop_id' => $shift->workshop_id,
            'comment' => $comment,
        ]);

        foreach ($filteredConsumptions as $consumption) {
            MovementMaterial::create([
                'material_id' => $consumption->material_id,
                'order_id' => $order->id,
                'roll_id' => $rolls[$consumption->material_id]->id,
                'quantity' => 1,
            ]);
        }
    }

    /**
     * Проверяет, что текущий пользователь имеет роль ОТК; иначе перенаправляет на главную киоска.
     */
    public function authorizeOtk(): void
    {
        $user = User::find(session('user_id'));

        if (! $user || ! $user->isOtk()) {
            redirect()->route('kiosk')->throwResponse();
        }
    }

    /**
     * Возвращает товары для проверки ОТК с фильтрацией по статусу, материалу, ширине и высоте.
     */
    public function getFilteredInspectionItems(Request $request, array|int $status, bool $orderByDesc = false): Collection
    {
        $query = MarketplaceOrderItem::query()
            ->with(['item', 'marketplaceOrder'])
            ->when(is_array($status), fn ($q) => $q->whereIn('status', $status))
            ->when(is_int($status), fn ($q) => $q->where('status', $status))
            ->when($request->filled('material'), fn ($q) => $q->whereHas('item', fn ($q) => $q->where('title', 'like', '%'.$request->material.'%')))
            ->when($request->filled('width'), fn ($q) => $q->whereHas('item', fn ($q) => $q->where('width', $request->width)))
            ->when($request->filled('height'), fn ($q) => $q->whereHas('item', fn ($q) => $q->where('height', $request->height)));

        if ($orderByDesc) {
            $query->orderByDesc('id');
        }

        return $query->get();
    }

    /**
     * Проверяет наличие упаковочных материалов нужного типа на рулонах текущей смены.
     */
    public function hasPackagingMaterials(MarketplaceItem $item, string $materialUsed, Shift $shift): bool
    {
        $consumptions = $item->consumption()->with('material')->get();
        $filteredConsumptions = $this->filterConsumptionsByMaterialUsed($consumptions, $materialUsed);

        foreach ($filteredConsumptions as $consumption) {
            if (! $this->findPackagingRoll($consumption->material_id, $shift)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Находит рулон упаковочного материала в цехе для текущей смены.
     */
    private function findPackagingRoll(int $materialId, Shift $shift): ?Roll
    {
        return Roll::query()
            ->where('material_id', $materialId)
            ->where('status', Roll::STATUS_IN_WORKSHOP)
            ->where('shift_id', $shift->id)
            ->first();
    }

    public static function canUseFilter(User $user): bool
    {
        if ($user->isAdmin() || $user->isStorekeeper()) {
            return true;
        }

        if ($user->isOtk()) {
            if (Setting::getValue('sticking_otk', session('kiosk_workshop_id')) === 'filter') {
                return true;
            }
        }

        if ($user->isSeamstress()) {
            if (Setting::getValue('sticking_seamstress', session('kiosk_workshop_id')) === 'filter') {
                return true;
            }
        }

        return false;
    }

    public static function canSticking(User $user): bool
    {
        if ($user->isAdmin() || $user->isStorekeeper()) {
            return true;
        }

        $workshopId = session('kiosk_workshop_id');

        if ($user->isOtk()) {
            if (Setting::getValue('sticking_otk', $workshopId) !== 'disabled') {
                return true;
            }
        }

        $isOtkOnShift = User::query()
            ->where('role_id', 5)
            ->where('shift_is_open', true)
            ->whereHas('shifts', fn ($q) => $q->where('workshop_id', $workshopId))
            ->exists();

        if ($user->isSeamstress() && ! $isOtkOnShift) {
            if (Setting::getValue('sticking_seamstress', $workshopId) !== 'disabled') {
                return true;
            }
        }

        return false;
    }
}
