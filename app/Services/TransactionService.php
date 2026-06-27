<?php

namespace App\Services;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\MarketplaceOrderItem;
use App\Models\Motivation;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransactionService
{
    public static function store(CreateTransactionRequest $request): bool
    {
        $moneyInCompany = TransactionService::getTotalByType($request, false, true);
        if ($request->type === 'company' && $request->transaction_type === 'out' && $request->amount > $moneyInCompany) {
            return false;
        }

        $isBonus = match ($request->type) {
            'bonus' => true,
            default => false
        };

        $user = User::query()->find($request->user_id);

        $finePhotoPath = self::storeFinePhoto($request);

        self::addTransaction(
            $user,
            $request->amount,
            $request->transaction_type,
            $request->title,
            $request->accrual_for_date,
            $request->type,
            $isBonus,
            $finePhotoPath,
        );

        $label = $isBonus ? 'бонусов' : 'денег';
        $userName = ($request->type === 'company') ? 'по компании' : "для пользователя {$user->name}";
        Log::channel('salary')->info(
            "Ручное начисление {$label} в размере {$request->amount} рублей ({$request->transaction_type}) {$userName}"
        );

        return true;
    }

    private static function addTransaction(?User $user, $amount, $transaction_type, $title, $accrual_for_date, $type, bool $isBonus, ?string $finePhoto = null): void
    {
        $status = $isBonus ? 0 : 1;

        if ($type === 'company') {
            $status = 2;
            $paid_at = now()->format('Y-m-d H:i:s');
        }

        Transaction::query()->create([
            'user_id' => $user->id ?? null,
            'title' => $title,
            'accrual_for_date' => $accrual_for_date,
            'amount' => $amount,
            'transaction_type' => $transaction_type,
            'status' => $status,
            'is_bonus' => $isBonus,
            'paid_at' => $paid_at ?? null,
            'fine_photo' => $finePhoto,
        ]);
    }

    /**
     * Сохраняет фото-доказательство штрафа на публичный диск.
     *
     * Фото принимается только для штрафа (transaction_type=in),
     * для остальных типов — игнорируется (двойная защита помимо UI).
     */
    private static function storeFinePhoto(CreateTransactionRequest $request): ?string
    {
        if ($request->transaction_type !== 'in' || ! $request->hasFile('fine_photo')) {
            return null;
        }

        $file = $request->file('fine_photo');
        $fileName = now()->format('Ymd_His').'_'.uniqid().'.'.$file->getClientOriginalExtension();

        Storage::disk('public')->putFileAs('fines', $file, $fileName);

        return 'fines/'.$fileName;
    }

    public static function activateHoldBonus(): void
    {
        $transactions = Transaction::query()
            ->whereDate('accrual_for_date', '<', now()->subDays(14))
            ->where('is_bonus', true)
            ->where('status', 0)
            ->get();

        if ($transactions->isEmpty()) {
            Log::channel('salary')->info('Сегодня нет бонусов для активации');

            return;
        }

        $logData = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'accrual_for_date' => $transaction->accrual_for_date,
            ];
        })->toArray();

        Log::channel('salary')->info('Активируются бонусы:', [
            'transactions' => $logData,
        ]);

        Transaction::query()
            ->whereIn('id', $transactions->pluck('id'))
            ->update(['status' => 1]);
    }

    public static function getSeamstressBalance(string $type, $isHoldBonus = false): int
    {
        $isBonus = match ($type) {
            'salary' => false,
            'bonus' => true,
            default => null,
        };

        $status = match ($type) {
            'salary' => 1,
            'bonus' => $isHoldBonus ? 0 : 1,
            default => null,
        };

        if (! isset($isBonus) || ! isset($status)) {
            return 0;
        }

        $query = Transaction::query()
            ->where('status', $status)
            ->where('is_bonus', $isBonus)
            ->where('user_id', auth()->id());

        $employeeOut = (clone $query)->where('transaction_type', 'in')->sum('amount');
        $employeeIn = (clone $query)->where('transaction_type', 'out')->sum('amount');

        return $employeeIn - $employeeOut;
    }

    public static function getFiltered(Request $request): Builder
    {
        $transactions = Transaction::query()
            ->orderBy('created_at', 'desc');

        if (! auth()->user()->isAdmin()) {
            $transactions->where('user_id', auth()->user()->id);
        } else {
            if ($request->user_id) {
                $transactions->where('user_id', $request->user_id);
            }
        }

        if ($request->date_start) {
            $transactions->where('accrual_for_date', '>=', $request->date_start);
        }

        if ($request->date_end) {
            $transactions->where('accrual_for_date', '<=', $request->date_end);
        }

        match ($request->type) {
            'salary' => $transactions->whereNotNull('user_id'),
            'company' => $transactions->whereNull('user_id'),
            default => null,
        };

        return $transactions;
    }

    public static function getLastPayouts(?User $user, int $count, bool $isBonus = false): array|\Illuminate\Support\Collection
    {
        if ($user) {
            return Transaction::query()
                ->where('user_id', $user->id)
                ->where('is_bonus', $isBonus)
                ->whereNotNull('paid_at')
                ->get()
                ->groupBy(function ($tx) {
                    return Carbon::parse($tx->paid_at)->toDateString();
                })
                ->map(function ($group, $payoutDate) {
                    $accrualDates = $group->pluck('accrual_for_date')
                        ->filter()
                        ->map(fn ($d) => Carbon::parse($d))
                        ->sort();

                    return [
                        'payout_date' => (Carbon::parse($payoutDate))->format('d/m/Y'),
                        'net_total' => $group->sum(function ($tx) {
                            return $tx->transaction_type === 'out' ? $tx->amount : (
                                $tx->transaction_type === 'in' ? '-'.$tx->amount : 0);
                        }),
                        'accrual_range' => $accrualDates->isEmpty() ? null : [
                            'from' => $accrualDates->first()->format('Y-m-d'),
                            'to' => $accrualDates->last()->format('Y-m-d'),
                        ],
                    ];
                })
                ->sortByDesc(fn ($item) => Carbon::createFromFormat('d/m/Y', $item['payout_date']))
                ->values()
                ->take($count);
        } else {
            return [];
        }
    }

    public static function getSumOfPayout(Request $request)
    {
        if ($request->start_date != null && $request->end_date != null) {
            $query = Transaction::query()
                ->where('user_id', $request->user_id)
                ->where('is_bonus', false)
                ->whereNull('paid_at')
                ->whereDate('accrual_for_date', '>=', $request->start_date)
                ->whereDate('accrual_for_date', '<=', $request->end_date);

            $employeeOut = (clone $query)->where('transaction_type', 'in')->sum('amount');
            $employeeIn = (clone $query)->where('transaction_type', 'out')->sum('amount');

            return $employeeIn - $employeeOut;
        }

        return 0;
    }

    public static function getOldestUnpaidSalaryEntry(?User $user): ?string
    {
        if ($user) {
            return Transaction::query()
                ->where('user_id', $user->id)
                ->where('is_bonus', false)
                ->whereNull('paid_at')
                ->orderBy('accrual_for_date', 'asc')
                ->value('accrual_for_date') ?? null;
        }

        return null;
    }

    public static function getTotalByType(Request $request, bool $isBonus, $company = false): float
    {
        $query = Transaction::query()
            ->where('is_bonus', $isBonus);

        if (! auth()->user()->isAdmin()) {
            $query = $query->where('user_id', auth()->id());
        }

        if ($company) {
            $query = $query->whereNotNull('paid_at');
        } else {
            $query = $query->whereNotNull('user_id')
                ->whereNull('paid_at');

            if ($request->user_id) {
                $query = $query->where('user_id', $request->user_id);
            }
        }

        if ($request->date_start && $request->date_end && ! $company) {
            $query = $query->whereBetween('accrual_for_date', [$request->date_start, $request->date_end]);
        }

        $employeeOut = (clone $query)->where('transaction_type', 'in')->sum('amount');
        $employeeIn = (clone $query)->where('transaction_type', 'out')->sum('amount');

        $result = $employeeIn - $employeeOut;
        if ($company) {
            $result = $employeeOut - $employeeIn;
        }

        return $result;
    }

    public static function getHoldBonus(?User $user): array|\Illuminate\Support\Collection
    {
        if ($user) {
            return Transaction::query()
                ->where('user_id', $user->id)
                ->where('is_bonus', true)
                ->whereIn('status', [0, 1])
                ->get()
                ->groupBy(function ($tx) {
                    return Carbon::parse($tx->accrual_for_date)->toDateString();
                })
                ->map(function ($group, $payoutDate) {
                    return [
                        'accrual_for_date' => $payoutDate,
                        'net_total' => $group->sum(function ($tx) {
                            return $tx->transaction_type === 'out' ? $tx->amount : (
                                $tx->transaction_type === 'in' ? '-'.$tx->amount : 0);
                        }),
                        'status' => $group->first()->status,
                        'date_pay' => (Carbon::parse($payoutDate)->addDays(14)->format('d/m/Y')),
                    ];
                })
                ->sortBy('accrual_for_date')
                ->values(); //                ->take(10)
        } else {
            return [];
        }
    }

    public static function getCashflowFiltered(Request $request)
    {
        $summary = Transaction::query()
            ->selectRaw("
                user_id,
                DATE(paid_at) AS paid_date,
                SUM(CASE WHEN transaction_type = 'out' THEN amount ELSE 0 END) -
                SUM(CASE WHEN transaction_type = 'in' THEN amount ELSE 0 END) AS net_balance,
                users.name AS user_name
            ")
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->whereNotNull('paid_at')
            ->where('is_bonus', 0)
            ->whereNotNull('user_id');

        if (! auth()->user()->isAdmin()) {
            $summary = $summary->where('user_id', auth()->id());
        }

        if ($request->date_start) {
            $summary = $summary->where('paid_at', '>=', $request->date_start.' 00:00:00');
        }

        if ($request->date_end) {
            $summary = $summary->where('paid_at', '<=', $request->date_end.' 23:59:59');
        }

        return $summary
            ->groupBy('transactions.user_id', DB::raw('DATE(transactions.paid_at)'), 'users.name')
            ->orderBy('paid_date', 'desc')
            ->orderBy('transactions.user_id');
    }

    public static function getBonusForTodayOrdersByUsers()
    {
        $user = auth()->user();
        $query = MarketplaceOrderItem::query();
        $value = 'bonus';

        if ($user->isCutter()) {
            $query->where('cutter_id', $user->id);
            $value = 'cutter_bonus';
        } elseif ($user->isSeamstress()) {
            $query->where('seamstress_id', $user->id);
            $value = $user->canSeamstressCut() ? 'bonus' : 'not_cutter_bonus';
        }

        $allWidth = $query
            ->whereDate('completed_at', today())
            ->with('item')
            ->get()
            ->sum(fn ($item) => $item->item->width ?? 0) / 100;

        return Motivation::query()
            ->where('user_id', auth()->user()->id)
            ->where('from', '<=', $allWidth)
            ->where('to', '>', $allWidth)
            ->value($value) ?? 0;
    }

    public static function penalizeUserForOrderCancellation(MarketplaceOrderItem $marketplaceOrderItem): void
    {
        $user = auth()->user();
        $amount = Setting::getValue('cancel_order_penalty');

        TransactionService::addTransaction(
            $user,
            $amount,
            'in',
            'Штраф за отмену заказа № '.$marketplaceOrderItem->marketplaceOrder->order_id,
            Carbon::now()->format('Y-m-d'),
            'salary',
            false
        );

        Log::channel('salary')
            ->warning('Начислен штраф в размере '.$amount.' рублей за отмену заказа № '
                .$marketplaceOrderItem->marketplaceOrder->order_id.
                ' сотруднику '.$user->name.' ( id - '.$user->id.')');
    }
}
