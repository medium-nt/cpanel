<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    public function index()
    {
        return view('suppliers.index', [
            'title' => 'Поставщики',
            'suppliers' => Supplier::query()->paginate(10),
        ]);
    }

    public function create()
    {
        return view('suppliers.create', [
            'title' => 'Добавить поставщика',
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|min:2|max:255',
            'phone' => 'required|string|min:2|max:255',
            'address' => 'required|string|min:2|max:255',
            'comment' => 'nullable|string|min:2|max:255',
        ];

        $validatedData = $request->validate($rules);

        $supplier = Supplier::query()->create($validatedData);

        Log::channel('system')->info('Создан поставщик', [
            'supplier_id' => $supplier->id,
            'title' => $supplier->title,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('suppliers.index')->with('success', 'Поставщик добавлен');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', [
            'title' => 'Изменить поставщика',
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $rules = [
            'title' => 'required|string|min:2|max:255',
            'phone' => 'required|string|min:2|max:255',
            'address' => 'required|string|min:2|max:255',
            'comment' => 'nullable|string|min:2|max:255',
        ];

        $validatedData = $request->validate($rules);
        $supplier->update($validatedData);

        Log::channel('system')->info('Обновлён поставщик', [
            'supplier_id' => $supplier->id,
            'changed' => collect($supplier->getChanges())->except(['updated_at'])->keys(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('suppliers.index')->with('success', 'Изменения сохранены');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->orders()->count() > 0) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Невозможно удалить поставщика, так как он используется в системе');
        }

        Log::channel('system')->warning('Удалён поставщик', [
            'supplier_id' => $supplier->id,
            'title' => $supplier->title,
            'deleted_by' => auth()->id(),
        ]);

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Поставщик удален');
    }
}
