<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceItem;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\Sku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            'items' => MarketplaceItem::query()->get(),
            'materials' => Material::query()->get()
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

        $materialsConsumption = [];
        foreach ($request->material_id as $key => $material_id) {
            if ($request->quantity[$key] > 0) {
                $materialsConsumption[] = [
                    'material_id' => $material_id,
                    'quantity' => $request->quantity[$key]
                ];
            }
        }

        $rules = [
            '*.material_id' => 'required|exists:materials,id',
            '*.quantity' => 'required|numeric|min:0.01',
        ];

        $validator = Validator::make($materialsConsumption, $rules);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

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

        $validatedMaterialsConsumption = $validator->validated();
        foreach ($validatedMaterialsConsumption as $item) {
            MaterialConsumption::query()->create([
                'item_id' => $itemId->id,
                'material_id' => $item['material_id'],
                'quantity' => $item['quantity']
            ]);
        }

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
            'materialsConsumption' => MaterialConsumption::query()->where('item_id', $marketplaceItem->id)->get()
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

        $materialsConsumption = [];
        foreach ($request->material_id as $key => $material_id) {
            if ($request->quantity[$key] > 0) {
                $materialsConsumption[] = [
                    'material_id' => $material_id,
                    'quantity' => $request->quantity[$key]
                ];
            }
        }

        $rules = [
            '*.material_id' => 'required|exists:materials,id',
            '*.quantity' => 'required|numeric|min:0.01',
        ];

        $validator = Validator::make($materialsConsumption, $rules);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

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

        $validatedMaterialsConsumption = $validator->validated();
        foreach ($validatedMaterialsConsumption as $item) {
            MaterialConsumption::query()->updateOrCreate(
                ['item_id' => $marketplaceItem->id, 'material_id' => $item['material_id']],
                ['quantity' => $item['quantity']]
            );
        }

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
