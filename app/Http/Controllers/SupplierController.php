<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('suppliers.index', [
            'title' => 'Поставщики',
            'suppliers' => Supplier::query()->paginate(10)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('suppliers.create', [
            'title' => 'Добавить поставщика'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|min:2|max:255'
        ];

        $validatedData = $request->validate($rules);
        Supplier::query()->create($validatedData);

        return redirect()->route('suppliers.index')->with('success', 'Поставщик добавлен');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', [
            'title' => 'Изменить поставщика',
            'supplier' => $supplier
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $rules = [
            'title' => 'required|string|min:2|max:255'
        ];

        $validatedData = $request->validate($rules);
        $supplier->update($validatedData);

        return redirect()->route('suppliers.index')->with('success', 'Изменения сохранены');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Поставщик удален');
    }
}
