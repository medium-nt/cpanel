<?php

namespace App\Services;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\MarketplaceOrderItem;
use App\Models\Motivation;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public static function store(CreateTransactionRequest $request): void
    {
        match ($request->type) {
            'bonus' => self::addTransaction($request, true),
            'salary' => self::addTransaction($request, false),
        };
    }

    public static function getSalaryTable($seamstresses, $startDate, $endDate): array
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

        // Собираем ставки швей заранее
        $rates = [];
        foreach ($seamstresses as $seamstress) {
            $rates[$seamstress->id] = $seamstress->salary_rate;
        }

        // Извлекаем данные одним общим запросом
        $data = MarketplaceOrderItem::query()
            ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
            ->whereIn('marketplace_order_items.seamstress_id', collect($seamstresses)->pluck('id')->all())
            ->whereBetween('marketplace_order_items.updated_at', [$startDate, $endDate])
            ->where('marketplace_order_items.status', 3)
            ->groupBy('marketplace_order_items.seamstress_id', DB::raw('DATE(marketplace_order_items.updated_at)'))
            ->select([
                'marketplace_order_items.seamstress_id',
                DB::raw('DATE(marketplace_order_items.updated_at) as salary_date'),
                DB::raw('SUM(marketplace_order_items.quantity * marketplace_items.width / 100) as total_salary')
            ])
            ->get();

        // Формируем результирующий массив
        $result = [];
        foreach ($data as $row) {
            if (!isset($result[$row->salary_date])) {
                $result[$row->salary_date] = [];
            }
            $result[$row->salary_date][$row->seamstress_id] = $row->total_salary * $rates[$row->seamstress_id];
        }

        // Проходим по всем необходимым датам и добавляем недостающие записи (для случаев отсутствия заказов)
        for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            if (!isset($result[$formattedDate])) {
                $result[$formattedDate] = [];
            }

            foreach ($seamstresses as $seamstress) {
                if (!isset($result[$formattedDate][$seamstress->id])) {
                    $result[$formattedDate][$seamstress->id] = 0;
                }
            }

            ksort($result[$formattedDate]);
        }

        ksort($result);

        return $result;
    }

    public static function getSalaryTable_new($seamstresses, $startDate, $endDate): array
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $startDate);
        $endDate = Carbon::createFromFormat('Y-m-d', $endDate);

        $endDate->addDay();

        $data = MarketplaceOrderItem::query()
            ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
            ->whereIn('marketplace_order_items.seamstress_id', collect($seamstresses)->pluck('id')->all())
            ->whereBetween('marketplace_order_items.completed_at', [$startDate, $endDate])
            ->groupBy('marketplace_order_items.seamstress_id', DB::raw('DATE(marketplace_order_items.completed_at)'))
            ->select([
                'marketplace_order_items.seamstress_id',
                DB::raw('DATE(marketplace_order_items.completed_at) as salary_date'),
                DB::raw('SUM(marketplace_order_items.quantity * marketplace_items.width) as total_salary')
            ])
            ->get();

        // Собираем ставки швей заранее
        $rates = [];
        foreach ($seamstresses as $seamstress) {
            $rates[$seamstress->id] = $seamstress->salary_rate;
        }

        $result = [];
        foreach ($data as $row) {
            if (!isset($result[$row->salary_date])) {
                $result[$row->salary_date] = [];
            }
            $result[$row->salary_date][$row->seamstress_id] =
                $row->total_salary / 100 * $rates[$row->seamstress_id]
            ;
        }

        // Проходим по всем необходимым датам и добавляем недостающие записи (для случаев отсутствия заказов)
        for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            if (!isset($result[$formattedDate])) {
                $result[$formattedDate] = [];
            }

            foreach ($seamstresses as $seamstress) {
                if (!isset($result[$formattedDate][$seamstress->id])) {
                    $result[$formattedDate][$seamstress->id] = 0;
                }
            }

            ksort($result[$formattedDate]);
        }

        ksort($result);

        unset($result[$endDate->format('Y-m-d')]);

        return $result;
    }

    public static function getSalaryTable_old($seamstresses, $startDate, $endDate): array
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $startDate);
        $endDate = Carbon::createFromFormat('Y-m-d', $endDate);

        $arrayAllSalary = [];
        for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {

            $arrayByDate = [];
            foreach ($seamstresses as $seamstress) {
                $itemsLengthInMeters = MarketplaceOrderItem::query()
                    ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
                    ->where('marketplace_order_items.seamstress_id', $seamstress->id)
                    ->whereDate('marketplace_order_items.updated_at', $date)
                    ->where('marketplace_order_items.status', 3)
                    ->selectRaw('SUM(marketplace_order_items.quantity * marketplace_items.width / 100) as total_salary')
                    ->first('total_salary'); // Запрашиваем первую запись (агрегатную выборку)

                $arrayByDate[$seamstress->id] = $itemsLengthInMeters->total_salary ?? 0;
            }

            $arrayAllSalary[$date->format('Y-m-d')] = $arrayByDate;
        }

        return $arrayAllSalary;
    }

    public static function accrualStorekeeperSalary(): void
    {
        $workers = Schedule::query()
            ->where('date', Carbon::now()->subDay()->format('Y-m-d'))
            ->get();

        foreach ($workers as $worker) {
            if ($worker->user->role->name == 'storekeeper') {

                $accrualForDate = \Carbon\Carbon::parse($worker->date);

                Transaction::query()->create([
                    'user_id' => $worker->user->id,
                    'title' => 'Зарплата за ' . $accrualForDate->format('d/m/Y'),
                    'accrual_for_date' => $accrualForDate->format('Y-m-d'),
                    'amount' => $worker->user->salary_rate,
                    'transaction_type' => 'in',
                    'status' => 1,
                ]);

                Log::channel('salary')
                    ->info('Добавили зарплату в размере ' . $worker->user->salary_rate . ' рублей для кладовщика ' . $worker->user->name);
            }
        }
    }

    private static function addTransaction($request, bool $isBonus): void
    {
        $user = User::query()->find($request->user_id);
        $amount = $request->amount;
        $type = $request->transaction_type;

        $status = $isBonus
            ? match ($type) {
                'in' => 0,
                'out' => 1,
            }
            : 1;

        Transaction::query()->create([
            'user_id' => $user->id,
            'title' => $request->title,
            'accrual_for_date' => $request->accrual_for_date,
            'amount' => $amount,
            'transaction_type' => $type,
            'status' => $status,
            'is_bonus' => $isBonus,
        ]);

        $label = $isBonus ? 'бонусов' : 'денег';
        Log::channel('salary')->info(
            "Ручное начисление {$label} в размере {$amount} рублей ({$type}) для пользователя {$user->name}"
        );
    }

    public static function activateHoldBonus(): void
    {
        Transaction::query()
            ->where('created_at', '<', now()->subDays(30))
            ->where('is_bonus', true)
            ->where('status', 0)
            ->update([
                    'status' => 1]
            );

        Log::channel('erp')
            ->info('Активировали бонусы, по которым прошло более 30 дней');
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

        $in = (clone $query)->where('transaction_type', 'in')->sum('amount');
        $out = (clone $query)->where('transaction_type', 'out')->sum('amount');

        return $in - $out;
    }

    public static function getFiltered($request): Builder
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
            $transactions->where('created_at', '>=', $request->date_start);
        }

        if ($request->date_end) {
            $transactions->where('created_at', '<=', $request->date_end . ' 23:59:59');
        }

        return $transactions;
    }

    public static function accrualSeamstressesSalary()
    {
        // Выбрать всех швей. По каждой швее выбрать все заказы за вчера
        $seamstresses = User::query()
            ->whereHas('role', fn($q) => $q->where('name', 'seamstress'))
            ->with([
                'marketplaceOrderItems' => fn($q) => $q
                    ->whereDate('completed_at', Carbon::yesterday()->format('Y-m-d'))
                    ->orderBy('completed_at')
                    ->with('item')])
            ->get();

        foreach ($seamstresses as $seamstress) {
            // Сложить общий метраж и на основании его высчитать тариф мотивации.
            $totalWidth = $seamstress->marketplaceOrderItems
                    ->sum(fn($marketplaceOrderItems) => $marketplaceOrderItems->item?->width ?? 0) / 100;

            $motivation = Motivation::query()
                ->where('user_id', $seamstress->id)
                ->where('from', '<=', $totalWidth)
                ->where('to', '>', $totalWidth)
                ->first();

            $allMotivationWithBonus = Motivation::query()
                ->where('user_id', $seamstress->id)
                ->get();

            if (!$motivation) {
//                Log::channel('erp')
//                    ->info("У швеи {$seamstress->name} нет мотивации за метраж {$totalWidth} м.");

                echo "ВНИМАНИЕ!!! У швеи {$seamstress->name} нет мотивации за метраж {$totalWidth} м. <br><br>";

                continue;
            }

            echo "Швея: {$seamstress->name}<br>";
            echo "Общий метраж: {$totalWidth} м.<br>";
            echo "Мотивация: от {$motivation->from} до {$motivation->to} метров, ставка = {$motivation->rate} руб., бонус = {$motivation->bonus}<br>";

            $allSalary = $allBonus = $allWidth = 0;
            foreach ($seamstress->marketplaceOrderItems as $marketplaceOrderItems) {
                // Проходим по каждому товару и начисляем зп и бонусы за них
                $width = $marketplaceOrderItems->item->width / 100 ?? 0;
                $allWidth += $width;

                $nowMotivation = $allMotivationWithBonus
                    ->where('from', '<=', $allWidth)
                    ->where('to', '>', $allWidth)
                    ->first();

                $bonus = 0;
                if ($nowMotivation->bonus) {
                    $bonus = $width * $nowMotivation->bonus;
                    $allBonus += $bonus;
                }

                $salary = $width * $motivation->rate;
                $allSalary += $salary;

                echo "<br>- Заказ #{$marketplaceOrderItems->id}, ширина: {$width} м. (сдан: {$marketplaceOrderItems->completed_at}). ";

                echo " ЗП за заказ: {$salary} руб., бонус: {$bonus} баллов.<br>";
            }

            echo "Всего: {$totalWidth} м, зп: {$allSalary} руб., бонус: {$allBonus} баллов.<br>";

            echo "<br>";
            echo "-----------------------------------------------------------------<br>";
            echo "<br>";
        }

        dd('end');

    }
}
