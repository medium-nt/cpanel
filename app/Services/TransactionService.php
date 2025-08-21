<?php

namespace App\Services;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\MarketplaceOrderItem;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
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
                Transaction::query()->create([
                    'user_id' => $worker->user->id,
                    'title' => 'Зарплата за ' . \Carbon\Carbon::parse($worker->date)->format('d/m/Y'),
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
}
