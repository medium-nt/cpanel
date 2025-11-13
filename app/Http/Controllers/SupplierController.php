<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

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

        Supplier::query()->create($validatedData);

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

        return redirect()->route('suppliers.index')->with('success', 'Изменения сохранены');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->orders()->count() > 0) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Невозможно удалить поставщика, так как он используется в системе');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Поставщик удален');
    }
}
