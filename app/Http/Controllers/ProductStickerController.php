<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveProductStickerRequest;
use App\Models\ProductSticker;

class ProductStickerController extends Controller
{
    public function index()
    {
        $stickers = ProductSticker::query()->orderBy('title')->get();

        return view('product_stickers.index', [
            'title' => 'Стикеры товаров',
            'stickers' => $stickers,
        ]);
    }

    public function create()
    {
        return view('product_stickers.create', [
            'title' => 'Добавить стикер',
        ]);
    }

    public function store(SaveProductStickerRequest $request)
    {
        ProductSticker::query()->create($request->all());

        return redirect()
            ->route('product_stickers.index')
            ->with('success', 'Стикер добавлен');
    }

    public function edit(ProductSticker $productSticker)
    {
        return view('product_stickers.edit', [
            'title' => 'Изменить стикер',
            'sticker' => $productSticker,
        ]);
    }

    public function update(SaveProductStickerRequest $request, ProductSticker $productSticker)
    {
        $productSticker->update($request->all());

        return redirect()
            ->route('product_stickers.index')
            ->with('success', 'Изменения сохранены');
    }

    public function destroy(ProductSticker $productSticker)
    {
        $productSticker->delete();

        return redirect()->route('product_stickers.index')->with('success', 'Стикер удален');
    }
}
