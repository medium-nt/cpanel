<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceItem;
use App\Models\Setting;
use App\Models\Workshop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkshopController extends Controller
{
    public function index(): View
    {
        // Загружаем цеха с количеством смен и сотрудников
        $workshops = Workshop::withCount('shifts')
            ->with(['shifts' => function ($query) {
                $query->withCount('users');
            }])
            ->get();

        return view('workshops.index', compact('workshops'));
    }

    public function create(): View
    {
        return view('workshops.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255',
        ]);

        Workshop::create([
            'title' => $validated['title'],
            'status' => Workshop::STATUS_ACTIVE,
        ]);

        return redirect()->route('workshops.index')->with('success', 'Цех создан');
    }

    public function edit(Workshop $workshop): View
    {
        // Загружаем смены этого цеха с количеством сотрудников
        $workshop->load(['shifts' => function ($query) {
            $query->withCount('users');
        }]);

        // Все товары маркетплейсов и ID товаров, разрешённых в этом цехе
        $marketplaceItems = MarketplaceItem::query()->orderBy('title')->get();
        $allowedItemIds = $workshop->allowedItems()->pluck('marketplace_items.id')->toArray();

        // Глобальные настройки (baseline) и цеховые переопределения
        $globalSettings = Setting::query()
            ->whereNull('workshop_id')
            ->pluck('value', 'name');

        $workshopSettings = Setting::query()
            ->where('workshop_id', $workshop->id)
            ->pluck('value', 'name');

        return view('workshops.edit', compact('workshop', 'marketplaceItems', 'allowedItemIds', 'globalSettings', 'workshopSettings'));
    }

    public function update(Request $request, Workshop $workshop): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255',
            'status' => 'required|in:active,inactive',
            'allowed_items' => 'nullable|array',
            'allowed_items.*' => 'exists:marketplace_items,id',
            'settings' => 'nullable|array',
        ]);

        $workshop->update([
            'title' => $validated['title'],
            'status' => $validated['status'],
        ]);

        // Синхронизация разрешённых товаров через pivot
        $workshop->allowedItems()->sync($validated['allowed_items'] ?? []);

        // Сохранение цеховых переопределений настроек
        $settings = $validated['settings'] ?? [];
        foreach ($settings as $name => $value) {
            if ($value !== null && $value !== '') {
                // Есть значение → создаём/обновляем цеховую настройку
                Setting::query()->updateOrCreate(
                    ['name' => $name, 'workshop_id' => $workshop->id],
                    ['value' => $value],
                );
            } else {
                // Пустое значение → удаляем цеховую настройку (fallback на глобальную)
                Setting::query()
                    ->where('name', $name)
                    ->where('workshop_id', $workshop->id)
                    ->delete();
            }
        }

        return redirect()->route('workshops.index')->with('success', 'Цех обновлён');
    }

    public function destroy(Workshop $workshop): RedirectResponse
    {
        $shiftsCount = $workshop->shifts()->count();
        if ($shiftsCount > 0) {
            return redirect()->route('workshops.index')
                ->with('error', 'Нельзя деактивировать цех, у которого есть смены. Сначала перенесите или удалите смены.');
        }

        $workshop->update(['status' => Workshop::STATUS_INACTIVE]);

        return redirect()->route('workshops.index')->with('success', 'Цех деактивирован');
    }
}
