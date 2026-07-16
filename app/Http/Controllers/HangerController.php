<?php

namespace App\Http\Controllers;

use App\Models\Hanger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HangerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('hangers.index', [
            'title' => 'Вешалки',
            'hangers' => Hanger::query()->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hangers.create', [
            'title' => 'Добавить вешалку',
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

        $hanger = new Hanger;
        $hanger->title = $request->title;
        $hanger->save();

        Log::channel('system')->info('Создана вешалка', [
            'hanger_id' => $hanger->id,
            'title' => $hanger->title,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('hangers.index')
            ->with('success', 'Вешалка добавлена');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Hanger $hanger)
    {
        return view('hangers.edit', [
            'title' => 'Редактирование вешалки',
            'hanger' => $hanger,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Hanger $hanger)
    {
        $request->validate([
            'title' => 'required|string|min:2|max:255',
        ], [
            'title.required' => 'Поле обязательно для заполнения',
            'title.string' => 'Поле должно быть строкой',
            'title.min' => 'Поле должно быть не менее :min символов',
            'title.max' => 'Поле должно быть не более :max символов',
        ]);

        $hanger->title = $request->title;
        $hanger->save();

        Log::channel('system')->info('Обновлена вешалка', [
            'hanger_id' => $hanger->id,
            'changed' => collect($hanger->getChanges())->except(['updated_at'])->keys(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('hangers.index')
            ->with('success', 'Вешалка обновлена');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hanger $hanger)
    {
        if ($hanger->orderItems()->count() > 0) {
            return redirect()->route('hangers.index')
                ->with('error', 'Нельзя удалять вешалку с заказами');
        }

        Log::channel('system')->warning('Удалена вешалка', [
            'hanger_id' => $hanger->id,
            'title' => $hanger->title,
            'deleted_by' => auth()->id(),
        ]);

        $hanger->delete();

        return redirect()
            ->route('hangers.index')
            ->with('success', 'Вешалка удалена');
    }
}
