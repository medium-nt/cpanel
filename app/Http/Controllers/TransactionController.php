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
            'total' => TransactionService::getTotalByType($request),
            'total_bonus' => TransactionService::getTotalByType($request, true),
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
        if($transaction->paid_at != null) {
            return back()
                ->with('error', 'Нельзя удалить выплаченную транзакцию');
        }

        $transaction->delete();

        return back()
            ->with('success', 'Транзакция удалена');
    }

    public function createPayoutSalary(Request $request)
    {
        $user = User::query()->find($request->user_id);

        return view('transactions.payout', [
            'title' => 'Выплата',
            'users' => User::query()->get(),
            'selected_user' => $user,
            'payouts' => TransactionService::getLastFivePayouts($user),
            'request' => $request,
            'net_payout' => TransactionService::getSumOfPayout($request),
            'oldestUnpaidSalaryDate' => TransactionService::getOldestUnpaidSalaryEntry($user)
        ]);
    }

    public function storePayoutSalary(Request $request)
    {
        Transaction::query()
            ->whereBetween('accrual_for_date', [
                $request->start_date,
                $request->end_date,
            ])
            ->where('user_id', $request->user_id)
            ->where('is_bonus', false)
            ->whereNull('paid_at')
            ->update([
                'paid_at' => now(),
                'status' => 2
            ]);

        return back()
            ->with('success', ' Зарплата выплачена');
    }

    public function createPayoutBonus(Request $request)
    {
        $user = User::query()->find($request->user_id);

        return view('transactions.payout_bonus', [
            'title' => 'Выплата',
            'users' => User::query()->get(),
            'selected_user' => $user,
            'payouts' => TransactionService::getLastFivePayouts($user, true),
            'hold_bonus' => TransactionService::getHoldBonus($user),
            'request' => $request,
            'net_payout' => TransactionService::getSumOfPayout($request),
            'oldestUnpaidSalaryDate' => TransactionService::getOldestUnpaidSalaryEntry($user)
        ]);

    }

    public function storePayoutBonus(Request $request)
    {
        Transaction::query()
            ->where('accrual_for_date', $request->accrual_for_date)
            ->where('user_id', $request->user_id)
            ->where('is_bonus', true)
            ->whereNull('paid_at')
            ->update([
                'paid_at' => now(),
                'status' => 2
            ]);

        return back()
            ->with('success', ' Бонусы выплачены');
    }
}
