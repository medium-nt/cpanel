<?php

namespace App\Services;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;

class TransactionService
{
    public static function store(CreateTransactionRequest $request): bool|RedirectResponse
    {
        Transaction::query()->create([
            'user_id' => $request->user_id ?? null,
            'title' => $request->title,
            'amount' => $request->amount,
            'transaction_type' => $request->transaction_type,
            'status' => 1,
        ]);

        return true;
    }
}
