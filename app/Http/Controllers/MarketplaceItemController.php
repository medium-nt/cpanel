<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceItem;
use App\Models\Sku;
use Illuminate\Http\Request;

class MarketplaceItemController extends Controller
{
    public function index(Request $request)
    {
        $queryParams = $request->except(['page']);

        $items = MarketplaceItem::query();

        if ($request->has('title')) {
            $items = $items->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->has('width')) {
            $items = $items->where('width', $request->width);
        }

        $items = $items->paginate(20);

        $items->appends($queryParams);

        return view('marketplace_items.index', [
            'title' => 'Товары маркетплейса',
            'items' => $items
        ]);
    }

    public function create()
    {
        return view('marketplace_items.create', [
            'title' => 'Добавить товар',
            'items' => MarketplaceItem::query()->get()
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|min:2|max:255',
            'width' => 'required|integer',
            'height' => 'required|integer',
            'ozon_sku' => 'nullable|string|min:3',
            'wb_sku' => 'nullable|string|min:3',
        ];

        $validatedData = $request->validate($rules);

        $itemId = MarketplaceItem::query()->create($validatedData);

        Sku::query()->create([
            'item_id' => $itemId->id,
            'sku' => $request->ozon_sku,
            'marketplace_id' => 1
        ]);

        Sku::query()->create([
            'item_id' => $itemId->id,
            'sku' => $request->wb_sku,
            'marketplace_id' => 2
        ]);

        return redirect()
            ->route('marketplace_items.index', ['title' => $request->title, 'width' => $request->width])
            ->with('success', 'Товар добавлен');
    }

    public function edit(MarketplaceItem $marketplaceItem)
    {
        return view('marketplace_items.edit', [
            'title' => 'Изменить товар',
            'item' => $marketplaceItem,
        ]);
    }

    public function update(Request $request, MarketplaceItem $marketplaceItem)
    {
        $rules = [
            'title' => 'required|string|min:2|max:255',
            'width' => 'required|integer',
            'height' => 'required|integer',
            'ozon_sku' => 'nullable|string|min:3',
            'wb_sku' => 'nullable|string|min:3',
        ];

        $validatedData = $request->validate($rules);

        $marketplaceItem->update($validatedData);

        Sku::query()
            ->where('item_id', $marketplaceItem->id)
            ->where('marketplace_id', 1)
            ->update([
                'sku' => $request->ozon_sku,
        ]);

        Sku::query()
            ->where('item_id', $marketplaceItem->id)
            ->where('marketplace_id', 2)
            ->update([
                'sku' => $request->wb_sku,
        ]);

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
