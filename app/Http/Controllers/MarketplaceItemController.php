<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveMarketplaceItemRequest;
use App\Models\MarketplaceItem;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Services\MarketplaceItemService;
use Illuminate\Http\Request;

class MarketplaceItemController extends Controller
{
    public function index(Request $request)
    {
        $items = MarketplaceItemService::getFilteredItems($request);
        $items = $items->paginate(20);

        $queryParams = $request->except(['page']);

        return view('marketplace_items.index', [
            'title' => 'Товары маркетплейса',
            'items' => $items->appends($queryParams),
            'titleMaterials' => MarketplaceItemService::getAllTitleMaterials(),
            'widthMaterials' => MarketplaceItemService::getAllWidthMaterials(),
        ]);
    }

    public function create()
    {
        return view('marketplace_items.create', [
            'title' => 'Добавить товар',
            'items' => MarketplaceItem::query()->get(),
            'materials' => Material::query()->get(),
        ]);
    }

    public function store(SaveMarketplaceItemRequest $request)
    {
        $marketplaceItem = MarketplaceItem::query()->create($request->all());

        MarketplaceItemService::saveSkus($marketplaceItem, $request);
        MarketplaceItemService::saveMaterialsConsumption($marketplaceItem, $request);

        return redirect()
            ->route('marketplace_items.index', ['title' => $request->title, 'width' => $request->width])
            ->with('success', 'Товар добавлен');
    }

    public function edit(MarketplaceItem $marketplaceItem)
    {
        return view('marketplace_items.edit', [
            'title' => 'Изменить товар',
            'item' => $marketplaceItem,
            'materials' => Material::query()->get(),
            'materialsConsumption' => MaterialConsumption::query()->where('item_id', $marketplaceItem->id)->get(),
        ]);
    }

    public function update(SaveMarketplaceItemRequest $request, MarketplaceItem $marketplaceItem)
    {
        $marketplaceItem->update($request->all());
        MarketplaceItemService::saveSkus($marketplaceItem, $request);
        MarketplaceItemService::saveMaterialsConsumption($marketplaceItem, $request);

        return redirect()
            ->route('marketplace_items.index', ['title' => $request->title, 'width' => $request->width])
            ->with('success', 'Изменения сохранены');
    }

    public function destroy(MarketplaceItem $marketplaceItem)
    {
        if ($marketplaceItem->marketplaceOrderItem()->count() > 0) {
            return redirect()->route('marketplace_items.index')
                ->with('error', 'Невозможно удалить товар, так как он используется в системе');
        }

        $marketplaceItem->delete();

        return redirect()->route('marketplace_items.index')->with('success', 'Товар удален');
    }
}
