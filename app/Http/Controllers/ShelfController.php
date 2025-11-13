<?php

namespace App\Http\Controllers;

use App\Models\Shelf;
use Illuminate\Http\Request;

class ShelfController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('shelves.index', [
            'title' => 'Полки на складе',
            'shelves' => Shelf::query()->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('shelves.create', [
            'title' => 'Добавить полку',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:2|max:255',
        ], [
            'title.required' => 'Поле обязательно для заполнения',
            'title.string' => 'Поле должно быть строкой',
            'title.min' => 'Поле должно быть не менее :min символов',
            'title.max' => 'Поле должно быть не более :max символов',
        ]);

        $shelf = new Shelf;
        $shelf->title = $request->title;
        $shelf->save();

        return redirect()
            ->route('shelves.index')
            ->with('success', 'Полка добавлена');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Shelf $shelf)
    {
        return view('shelves.edit', [
            'title' => 'Редактирование полки',
            'shelf' => $shelf,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shelf $shelf)
    {
        $request->validate([
            'title' => 'required|string|min:2|max:255',
        ], [
            'title.required' => 'Поле обязательно для заполнения',
            'title.string' => 'Поле должно быть строкой',
            'title.min' => 'Поле должно быть не менее :min символов',
            'title.max' => 'Поле должно быть не более :max символов',
        ]);

        $shelf->title = $request->title;
        $shelf->save();

        return redirect()
            ->route('shelves.index')
            ->with('success', 'Полка обновлена');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shelf $shelf)
    {
        if ($shelf->orderItems()->count() > 0) {
            return redirect()->route('shelves.index')
                ->with('error', 'Нельзя удалять полку с заказами');
        }

        $shelf->delete();

        return redirect()
            ->route('shelves.index')
            ->with('success', 'Полка удалена');
    }
}
