<?php

namespace App\Http\Controllers;

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

        return view('workshops.edit', compact('workshop'));
    }

    public function update(Request $request, Workshop $workshop): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $workshop->update($validated);

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
