<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceItem;
use Illuminate\Http\Request;

class MarketplaceItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('marketplace_items.index', [
            'title' => 'Товары маркетплейса',
            'items' => MarketplaceItem::query()->paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('marketplace_items.create', [
            'title' => 'Добавить товар',
            'items' => MarketplaceItem::query()->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|min:2|max:255',
            'sku' => 'required|string|min:2|max:255',
            'width' => 'required|integer',
            'height' => 'required|integer',
            'marketplace_id' => 'required|integer',
        ];

        $validatedData = $request->validate($rules);
        MarketplaceItem::query()->create($validatedData);

        return redirect()->route('marketplace_items.index')->with('success', 'Товар добавлен');
    }

    /**
     * Display the specified resource.
     */
    public function show(MarketplaceItem $marketplaceItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MarketplaceItem $marketplaceItem)
    {
        return view('marketplace_items.edit', [
            'title' => 'Изменить товар',
            'item' => $marketplaceItem,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MarketplaceItem $marketplaceItem)
    {
        $rules = [
            'title' => 'required|string|min:2|max:255',
            'sku' => 'required|string|min:2|max:255',
            'width' => 'required|integer',
            'height' => 'required|integer',
            'marketplace_id' => 'required|integer',
        ];

        $validatedData = $request->validate($rules);
        $marketplaceItem->update($validatedData);

        return redirect()->route('marketplace_items.index')->with('success', 'Изменения сохранены');
    }

    /**
     * Remove the specified resource from storage.
     */
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
