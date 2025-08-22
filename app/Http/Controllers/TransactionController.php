<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        return view('transactions.index', [
            'title' => 'Финансы',
            'users' => User::query()->get(),
            'transactions' => TransactionService::getFiltered($request)
                ->paginate(10)
                ->withQueryString()
        ]);
    }

    public function create($type)
    {
        $typeName = match ($type) {
            'salary' => 'зарплатой',
            'bonus' => 'бонусами',
        };

        return view('transactions.create', [
            'type' => $type,
            'title' => 'Добавить операцию с ' . $typeName,
            'users' => User::query()->get()
        ]);
    }

    public function store(CreateTransactionRequest $request)
    {
        TransactionService::store($request);

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Операция добавлена');
    }

    public function getSalaryTable(Request $request)
    {
        if (auth()->user()->role->name == 'admin') {
            $employees = User::query()->where('role_id', '1')->get();
        } else {
            $employees = User::query()->where('id', auth()->user()->id)->get();
        }

        $startDate = $request->date_start ?? now()->format('Y-m-d');
        $endDate = $request->date_end ?? now()->format('Y-m-d');

        return view('transactions.salary', [
            'title' => 'Зарплата',
            'seamstresses' => $employees,
            'seamstressesSalary' => TransactionService::getSalaryTable_new($employees, $startDate, $endDate)
        ]);
    }

    public function edit(Transaction $transaction)
    {
        //
    }

    public function update(Request $request, Transaction $transaction)
    {
        //
    }

    public function destroy(Transaction $transaction)
    {
        //
    }
}
