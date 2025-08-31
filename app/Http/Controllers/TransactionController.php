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
        $title = 'Финансы';
        if(auth()->user()->role->name == 'admin') {
            $title .= ' компании';
        }

        return view('transactions.index', [
            'title' => $title,
            'request' => $request,
            'users' => User::query()->get(),
            'totalInCompany' => TransactionService::getTotalByType($request, false, true),
            'total' => TransactionService::getTotalByType($request, false),
            'total_bonus' => TransactionService::getTotalByType($request, true),
            'cashflow' => TransactionService::getCashflowFiltered($request),
            'transactions' => TransactionService::getFiltered($request)
                ->paginate(10)
                ->withQueryString()
        ]);
    }

    public function create($type)
    {
        $typeName = match ($type) {
            'salary' => 'с зарплатой',
            'bonus' => 'с бонусами',
            'company' => 'компании',
        };

        return view('transactions.create', [
            'type' => $type,
            'title' => 'Добавить операцию ' . $typeName,
            'users' => User::query()->get()
        ]);
    }

    public function store(CreateTransactionRequest $request)
    {
        $result = TransactionService::store($request);

        if (!$result) {
            return back()
                ->with('error', 'Недостаточно денег в кассе')
                ->withInput();
        }

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Операция добавлена');
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
            'payouts' => TransactionService::getLastPayouts($user, 10),
            'request' => $request,
            'net_payout' => TransactionService::getSumOfPayout($request),
            'oldestUnpaidSalaryDate' => TransactionService::getOldestUnpaidSalaryEntry($user),
            'moneyInCompany' => TransactionService::getTotalByType($request, false, true)
        ]);
    }

    public function storePayoutSalary(Request $request)
    {
        $query = Transaction::query()
            ->whereBetween('accrual_for_date', [
                $request->start_date,
                $request->end_date,
            ])
            ->where('user_id', $request->user_id)
            ->where('is_bonus', false)
            ->whereNull('paid_at');

        $employeeOut = (clone $query)->where('transaction_type', 'in')->sum('amount');
        $employeeIn = (clone $query)->where('transaction_type', 'out')->sum('amount');
        $result = $employeeIn - $employeeOut;

        $moneyInCompany = TransactionService::getTotalByType($request, false, true);
        if ($result > $moneyInCompany) {
            return back()->with('error', 'Недостаточно денег для выплаты');
        }

        (clone $query)
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
            'payouts' => TransactionService::getLastPayouts($user, 10, true),
            'hold_bonus' => TransactionService::getHoldBonus($user),
            'request' => $request,
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
