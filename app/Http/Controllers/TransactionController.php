<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        return view('transactions.index', [
            'title' => 'Финансы',
            'transactions' => Transaction::query()
                ->orderBy('created_at', 'desc')
                ->paginate(10)
        ]);
    }

    public function create()
    {
        return view('transactions.create', [
            'title' => 'Добавить операцию',
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
