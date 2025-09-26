<?php

namespace App\Services;

use App\Http\Requests\SaveMarketplaceItemRequest;
use App\Models\MarketplaceItem;
use App\Models\MaterialConsumption;
use App\Models\Sku;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class MarketplaceItemService
{
    public static function getFilteredItems(Request $request)
    {
        $items = MarketplaceItem::query();

        if ($request->has('title')) {
            $items = $items->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->has('width')) {
            $items = $items->where('width', $request->width);
        }

        return $items;
    }

    public static function saveSkus(MarketplaceItem $marketplaceItem, SaveMarketplaceItemRequest|Request $request): void
    {
        $marketplaces = [
            ['id' => 1, 'sku_field' => 'ozon_sku'],
            ['id' => 2, 'sku_field' => 'wb_sku'],
        ];

        foreach ($marketplaces as $marketplace) {
            if ($request->filled($marketplace['sku_field'])) {
                Sku::query()
                    ->updateOrCreate(
                        [
                            'item_id' => $marketplaceItem->id,
                            'marketplace_id' => $marketplace['id'],
                        ],
                        [
                            'sku' => $request->{$marketplace['sku_field']},
                        ]
                    );
            }
        }
    }

    public static function saveMaterialsConsumption(MarketplaceItem $marketplaceItem, SaveMarketplaceItemRequest|Request $request): void
    {
        if ($request->material_id == null) {
            return;
        }

        foreach ($request->material_id as $index => $material_id) {
            if ($request->quantity[$index] > 0) {
                MaterialConsumption::query()
                    ->updateOrCreate(
                        ['item_id' => $marketplaceItem->id, 'material_id' => $material_id],
                        ['quantity' => $request->quantity[$index]]
                    );
            }
        }
    }

    public static function getAllTitleMaterials(): Collection
    {
        return MarketplaceItem::query()
            ->select('title')
            ->distinct()
            ->orderBy('title')
            ->get();
    }

    public static function getAllWidthMaterials(): Collection
    {
        return MarketplaceItem::query()
            ->select('width')
            ->distinct()
            ->orderBy('width')
            ->get();
    }

    public static function getAllHeightMaterials(): Collection
    {
        return MarketplaceItem::query()
            ->select('height')
            ->distinct()
            ->orderBy('height')
            ->get();
    }

}
