<?php

namespace App\Services;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrderItem;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class KioskService
{
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

    public function deductPackagingMaterials(MarketplaceItem $item, string $materialUsed, string $comment): void
    {
        $consumptions = $item->consumption()->with('material')->get();
        $filteredConsumptions = $this->filterConsumptionsByMaterialUsed($consumptions, $materialUsed);

        $order = Order::query()->create([
            'type_movement' => 3,
            'status' => 3,
            'comment' => $comment,
        ]);

        foreach ($filteredConsumptions as $consumption) {
            MovementMaterial::create([
                'material_id' => $consumption->material_id,
                'order_id' => $order->id,
                'quantity' => 1,
            ]);
        }
    }

    public function authorizeOtk(): void
    {
        $user = User::find(session('user_id'));

        if (! $user || ! $user->isOtk()) {
            redirect()->route('kiosk')->throwResponse();
        }
    }

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

    public function hasPackagingMaterials(MarketplaceItem $item, string $materialUsed): bool
    {
        $consumptions = $item->consumption()->with('material')->get();
        $filteredConsumptions = $this->filterConsumptionsByMaterialUsed($consumptions, $materialUsed);

        foreach ($filteredConsumptions as $consumption) {
            $inWorkshop = InventoryService::materialInWorkshop($consumption->material_id);

            if ($inWorkshop < 1) {
                return false;
            }
        }

        return true;
    }
}
