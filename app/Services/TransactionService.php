<?php

namespace App\Services;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\MarketplaceOrderItem;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
}
