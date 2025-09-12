<?php

namespace App\Services;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\MarketplaceOrderItem;
use App\Models\Motivation;
use App\Models\Rate;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public static function store(CreateTransactionRequest $request): bool
    {
        $moneyInCompany = TransactionService::getTotalByType($request, false, true);
        if ($request->type === 'company' && $request->transaction_type === 'out' && $request->amount > $moneyInCompany) {
            return false;
        }

        $isBonus = match ($request->type) {
            'salary', 'company' => false,
            'bonus' => true,
        };

        $user = User::query()->find($request->user_id);

        self::addTransaction(
            $user,
            $request->amount,
            $request->transaction_type,
            $request->title,
            $request->accrual_for_date,
            $request->type,
            $isBonus,
        );

        $label = $isBonus ? 'бонусов' : 'денег';
        $userName = ($request->type === 'company') ? 'по компании' : "для пользователя {$user->name}";
        Log::channel('salary')->info(
            "Ручное начисление {$label} в размере {$request->amount} рублей ({$request->transaction_type}) {$userName}"
        );

        return true;
    }

    public static function accrualStorekeeperSalary(): void
    {
        $workers = Schedule::query()
            ->where('date', Carbon::now()->subDay()->format('Y-m-d'))
            ->get();

        foreach ($workers as $worker) {
            if ($worker->user && $worker->user->role && $worker->user->role->name === 'storekeeper') {

                $accrualForDate = \Carbon\Carbon::parse($worker->date);

                Transaction::query()->create([
                    'user_id' => $worker->user->id,
                    'title' => 'Зарплата за ' . $accrualForDate->format('d/m/Y'),
                    'accrual_for_date' => $accrualForDate->format('Y-m-d'),
                    'amount' => $worker->user->salary_rate,
                    'transaction_type' => 'out',
                    'status' => 1,
                ]);

                Log::channel('salary')
                    ->info('Добавили зарплату в размере ' . $worker->user->salary_rate . ' рублей для кладовщика ' . $worker->user->name);
            }
        }
    }

    private static function addTransaction(?User $user, $amount, $transaction_type, $title, $accrual_for_date, $type, bool $isBonus): void
    {
        $status = $isBonus
            ? match ($transaction_type) {
                'out' => 0,
                'in' => 1,
            }
            : 1;

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
            'paid_at' => $paid_at ?? null
        ]);
    }

    public static function activateHoldBonus(): void
    {
        Transaction::query()
            ->where('created_at', '<', now()->subDays(14))
            ->where('is_bonus', true)
            ->where('status', 0)
            ->update([
                'status' => 1]
            );

        Log::channel('erp')
            ->info('Активировали бонусы, по которым прошло более 14 дней');
    }

    public static function getSeamstressBalance(string $type): int
    {
        $isBonus = match ($type) {
            'salary' => false,
            'bonus' => true,
            default => null,
        };

        $status = match ($type) {
            'salary' => 1,
            'bonus' => 0,
            default => null,
        };

        if (!isset($isBonus) || !isset($status)) {
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

        if (auth()->user()->role->name != 'admin') {
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

        return $transactions;
    }

    public static function accrualSeamstressesSalary($test = false): void
    {
        // Выбрать всех швей. По каждой швее выбрать все заказы выполненные за вчера
        $seamstresses = self::getSeamstressesWithOrders();

        foreach ($seamstresses as $seamstress) {
            self::processAccrual($seamstress, $test);
        }

        dd('end');
    }

    public static function accrualCuttersSalary($test = false): void
    {
        // Выбрать всех закройщиков. По каждому закройщику выбрать все заказы выполненные за вчера
        $cutters = self::getCuttersWithOrders();

        foreach ($cutters as $cutter) {
            self::processAccrual($cutter, $test);
        }

        dd('end');
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
                        ->map(fn($d) => Carbon::parse($d))
                        ->sort();
                    return [
                        'payout_date' => (Carbon::parse($payoutDate))->format('d/m/Y'),
                        'net_total' => $group->sum(function ($tx) {
                            return $tx->transaction_type === 'out' ? $tx->amount : (
                            $tx->transaction_type === 'in' ? -$tx->amount : 0);
                        }),
                        'accrual_range' => $accrualDates->isEmpty() ? null : [
                            'from' => $accrualDates->first()->format('Y-m-d'),
                            'to'   => $accrualDates->last()->format('Y-m-d'),
                        ],
                    ];
                })
                ->sortByDesc('payout_date')
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

        if(auth()->user()->role->name !== 'admin') {
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

        if ($request->date_start && $request->date_end && !$company) {
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
                            $tx->transaction_type === 'in' ? -$tx->amount : 0);
                        }),
                        'status' => $group->first()->status,
                        'date_pay' => (Carbon::parse($payoutDate)->addDays(30)->format('d/m/Y')),
                    ];
                })
                ->sortBy('accrual_for_date')
                ->values()
                ->take(10);
        } else {
            return [];
        }
    }

    public static function getCashflowFiltered(Request $request): Collection|\Illuminate\Support\Collection
    {
        $summary = Transaction::query()
            ->selectRaw("
                user_id,
                DATE(paid_at) AS paid_date,
                SUM(CASE WHEN transaction_type = 'out' THEN amount ELSE 0 END) -
                SUM(CASE WHEN transaction_type = 'in' THEN amount ELSE 0 END) AS net_balance
            ")
            ->whereNotNull('paid_at')
            ->where('is_bonus', 0)
            ->whereNotNull('user_id');

        if (auth()->user()->role->name !== 'admin') {
            $summary = $summary->where('user_id', auth()->id());
        }

        if ($request->date_start) {
            $summary = $summary->where('paid_at', '>=', $request->date_start . ' 00:00:00');
        }

        if ($request->date_end) {
            $summary = $summary->where('paid_at', '<=', $request->date_end . ' 23:59:59');
        }

        return $summary->groupBy('user_id', DB::raw("DATE(paid_at)"))
            ->orderBy('paid_date', 'desc')
            ->orderBy('user_id')
            ->get()
            ->load('user') // подтягивает связь
            ->transform(function ($row) {
                $row->user_name = $row->user->name ?? '—';
                return $row;
            });
    }

    public static function getBonusForTodayOrdersByUsers()
    {
        $allWidth = MarketplaceOrderItem::query()
                ->where('seamstress_id', auth()->id())
                ->whereDate('completed_at', today())
                ->with('item')
                ->get()
                ->sum(fn($item) => $item->item->width ?? 0) / 100;

        return Motivation::query()
            ->where('user_id', auth()->user()->id)
            ->where('from', '<=', $allWidth)
            ->where('to', '>', $allWidth)
            ->value('bonus') ?? 0;
    }

    private static function getSeamstressesWithOrders(): Collection
    {
        return User::query()
            ->whereHas('role', fn($q) => $q->where('name', 'seamstress'))
            ->with([
                'marketplaceOrderItems' => fn($q) => $q
                    ->whereDate('completed_at', Carbon::yesterday()->format('Y-m-d'))
                    ->orderBy('completed_at')
                    ->with('item')])
            ->get();
    }

    private static function getCuttersWithOrders(): Collection
    {
        return User::query()
            ->whereHas('role', fn($q) => $q->where('name', 'cutter'))
            ->with([
                'marketplaceOrderItemsByCutter' => fn($q) => $q
                    ->whereDate('cutting_completed_at', Carbon::yesterday()->format('Y-m-d'))
                    ->orderBy('cutting_completed_at')
                    ->with('item')])
            ->get();
    }

    private static function processAccrual(mixed $user, mixed $test): void
    {
        if ($user->role->name == 'seamstress') {
            $roleName = 'Швея';
            $marketplaceOrderItem = $user->marketplaceOrderItems;
        } else {
            $roleName = 'Закройщик';
            $marketplaceOrderItem = $user->marketplaceOrderItemsByCutter;
        }

        echo "<b>$roleName: $user->name</b><br>";

        $motivations = Motivation::query()
            ->where('user_id', $user->id)
            ->get();

        $result = [
            'allSalary' => 0,
            'allBonus' => 0,
            'allWidth' => 0
        ];

        foreach ($marketplaceOrderItem as $marketplaceOrderItems) {
            // Проходим по каждому товару и начисляем зп и бонусы за них
            $result['allWidth'] += $marketplaceOrderItems->item->width / 100 ?? 0;
            $result = self::processAccrualMotivationAndSalary($user, $marketplaceOrderItems, $motivations, $result, $test);
        }

        $totalWidth = $user->marketplaceOrderItems
                ->sum(fn($marketplaceOrderItems) => $marketplaceOrderItems->item?->width ?? 0) / 100;

        echo "Всего: $totalWidth м, зп: {$result['allSalary']} руб., бонус: {$result['allBonus']} баллов.<br>";

        echo "<br>";
        echo "-----------------------------------------------------------------<br>";
        echo "<br>";
    }

    private static function processAccrualMotivationAndSalary($user, $marketplaceOrderItems, $motivations, $result, $test): array
    {
        $orderId = $marketplaceOrderItems->marketplaceOrder->order_id;
        $width = $marketplaceOrderItems->item->width / 100 ?? 0;

        $resultDetails = self::getCompensationDetails($motivations, $result['allWidth'], $user, $marketplaceOrderItems);
        $nowMotivationBonus = $resultDetails['nowMotivationBonus'];
        $salary = $resultDetails['salary'];

        $bonus = 0;
        if ($nowMotivationBonus > 0) {
            $bonus = $width * $nowMotivationBonus;

            if (!$test) {
                self::addTransaction(
                    $user,
                    $bonus,
                    'out',
                    "Бонус за заказ #$orderId",
                    $marketplaceOrderItems->completed_at,
                    'bonus',
                    true,
                );
            }
        }

        if (!$test) {
            self::addTransaction(
                $user,
                $salary,
                'out',
                "ЗП за заказ #$orderId",
                $marketplaceOrderItems->completed_at,
                'salary',
                false,
            );

            $roleName = match ($user->role->name) {
                'seamstress' => 'Швея',
                'cutter' => 'Закройщик',
                default => 'НЕТ РОЛИ',
            };

            Log::channel('salary')
                ->info("$roleName: $user->name. Начисляем З/П $salary руб. и бонус $bonus баллов за заказ #$orderId, ширина: $width м.");
        }

        echo "<br>- Заказ #$marketplaceOrderItems->id ($orderId), ширина: $width м. ";
        echo " ЗП за заказ: $salary руб., бонус: $bonus баллов.<br>";

        return [
            'allSalary' => $result['allSalary'] + $salary,
            'allBonus' => $result['allBonus'] + $bonus,
            'allWidth' => $result['allWidth']
        ];
    }

    public static function getCompensationDetails($motivations, $allWidth, $user, $marketplaceOrderItems): array
    {
        $nowMotivationBonus = $motivations
            ->where('from', '<=', $allWidth)
            ->where('to', '>', $allWidth);

        $salary = Rate::query()
            ->where('user_id', $user->id)
            ->where('width', $marketplaceOrderItems->item->width);

        if($user->role->name == 'cutter') {
            $nowMotivationBonus = $nowMotivationBonus->value('cutter_bonus') ?? 0;
            $salary = $salary->value('cutter_rate') ?? 0;
        }

        if($user->role->name == 'seamstress') {
            if ($user->is_cutter) {
                $nowMotivationBonus = $nowMotivationBonus->value('bonus') ?? 0;
                $salary = $salary->value('rate') ?? 0;
            } else {
                $nowMotivationBonus = $nowMotivationBonus->value('not_cutter_bonus') ?? 0;
                $salary = $salary->value('not_cutter_rate') ?? 0;
            }
        }

        return [
            'nowMotivationBonus' => $nowMotivationBonus,
            'salary' => $salary,
        ];
    }

}
