<?php

namespace App\Services;

use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\Schedule;
use App\Models\Tariff;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserTariff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActionAccrualService
{
    /**
     * Начислить за действие (обрабатывает все товары сотрудника за день)
     */
    public function accrualForAction(string $action, Carbon $date, bool $test = false): void
    {
        $russianAction = UserTariff::getRussianAction($action);

        // Находим ВСЕ user_tariff для этого действия (все type и is_bonus)
        $allUserTariffs = UserTariff::query()
            ->where('action', $russianAction)
            ->whereHas('user', fn ($q) => $q->whereNotNull('role_id'))
            ->with('user')
            ->get();

        if ($allUserTariffs->isEmpty()) {
            return;
        }

        // Группируем по пользователям
        $groupedByUser = $allUserTariffs->groupBy('user_id');

        foreach ($groupedByUser as $userId => $userTariffs) {
            $this->processUserItems($userTariffs, $action, $date, $test);
        }
    }

    /**
     * Обработать все товары пользователя
     */
    private function processUserItems($userTariffs, string $action, Carbon $date, bool $test): void
    {
        $user = $userTariffs->first()->user;

        $userIdField = match ($action) {
            'sewing' => 'seamstress_id',
            'cutting' => 'cutter_id',
            'repacking', 'sticking' => 'otk_id',
            default => null,
        };

        $dateField = match ($action) {
            'sewing' => 'completed_at',
            'cutting' => 'cutting_completed_at',
            'repacking', 'sticking' => 'packed_at',
            default => null,
        };

        if (! $userIdField || ! $dateField) {
            return;
        }

        $items = MarketplaceOrderItem::query()
            ->where($userIdField, $user->id)
            ->whereDate($dateField, $date)
            ->with(['item', 'item.consumption.material', 'marketplaceOrder'])
            ->orderBy($dateField)
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        // Разделяем user_tariffs по type
        $perMeterTariffs = $userTariffs->where('type', 'per_meter');
        $perPieceTariffs = $userTariffs->where('type', 'per_piece');

        $cumulativeMeters = 0;

        foreach ($items as $item) {
            $material = null;
            if ($item->item?->consumption) {
                $firstConsumption = $item->item->consumption->where('material.type_id', 1)->first();
                $material = $firstConsumption?->material;
            }

            if (! $material) {
                Log::channel('salary')->error(
                    "Не найден материал для товара {$item->id}, пользователь: {$user->name}"
                );
                $cumulativeMeters += ($item->item?->width ?? 0) / 100;

                continue;
            }

            $widthMeters = ($item->item?->width ?? 0) / 100;
            $widthCm = $item->item?->width;

            // Собираем результаты начисления
            $salaryResults = [];
            $bonusResults = [];

            // === per_meter ===
            foreach ($perMeterTariffs as $userTariff) {
                $result = $this->calculateForItem(
                    $userTariff,
                    $material,
                    $widthMeters,
                    $cumulativeMeters
                );

                if ($result['amount'] > 0) {
                    if ($userTariff->is_bonus) {
                        $bonusResults[] = $result;
                    } else {
                        $salaryResults[] = $result;
                    }
                    $this->createTransaction($item, $result, $user, $date, $test, $action);
                }
            }

            // === per_piece ===
            foreach ($perPieceTariffs as $userTariff) {
                $tariff = $this->findTariffPerPiece($userTariff, $material, $widthCm);
                if ($tariff) {
                    $amount = (float) $tariff->value;
                    if ($amount > 0) {
                        $result = [
                            'amount' => $amount,
                            'split' => false,
                            'is_bonus' => $userTariff->is_bonus,
                        ];

                        if ($userTariff->is_bonus) {
                            $bonusResults[] = $result;
                        } else {
                            $salaryResults[] = $result;
                        }
                        $this->createTransactionForPiece($item, $tariff, $amount, $user, $date, $test, $action);
                    }
                }
            }

            // Лог с суммами
            $this->logAccrual($item, $user, $salaryResults, $bonusResults, $cumulativeMeters + $widthMeters, $test);

            $cumulativeMeters += $widthMeters;
        }
    }

    /**
     * Рассчитать сумму для товара (с учетом разделения при переходе границы range)
     */
    private function calculateForItem(
        UserTariff $userTariff,
        Material $material,
        float $widthMeters,
        float $cumulativeMeters
    ): array {
        // Находим тариф ДО
        $tariffBefore = $this->findTariffByCumulative($userTariff, $material, $cumulativeMeters);

        if (! $tariffBefore) {
            return [
                'amount' => 0,
                'split' => false,
                'is_bonus' => $userTariff->is_bonus,
            ];
        }

        // Находим тариф ПОСЛЕ
        $cumulativeAfter = $cumulativeMeters + $widthMeters;
        $tariffAfter = $this->findTariffByCumulative($userTariff, $material, $cumulativeAfter);

        if (! $tariffAfter) {
            return [
                'amount' => 0,
                'split' => false,
                'is_bonus' => $userTariff->is_bonus,
            ];
        }

        // Если тарифы одинаковые — простой расчет
        if ($tariffBefore->id === $tariffAfter->id) {
            return [
                'amount' => $widthMeters * (float) $tariffBefore->value,
                'split' => false,
                'is_bonus' => $userTariff->is_bonus,
            ];
        }

        // Тарифы разные — делим на 2 части
        $rangeEnd = $this->getRangeEnd($tariffBefore->range);
        $widthInOldRange = $rangeEnd - $cumulativeMeters;
        $widthInNewRange = $widthMeters - $widthInOldRange;

        $amountOld = $widthInOldRange * (float) $tariffBefore->value;
        $amountNew = $widthInNewRange * (float) $tariffAfter->value;

        return [
            'amount' => $amountOld + $amountNew,
            'split' => true,
            'is_bonus' => $userTariff->is_bonus,
        ];
    }

    /**
     * Найти тариф per_meter по кумулятивному метражу
     */
    private function findTariffByCumulative(UserTariff $userTariff, Material $material, float $cumulativeMeters): ?Tariff
    {
        $query = Tariff::query()
            ->where('user_tariff_id', $userTariff->id)
            ->where('material_id', $material->id);

        $allTariffs = $query->get();

        if ($allTariffs->isEmpty()) {
            return null;
        }

        return $allTariffs->first(fn (Tariff $t) => $t->range !== null && $this->isInRange($cumulativeMeters, $t->range));
    }

    /**
     * Найти тариф per_piece
     */
    private function findTariffPerPiece(UserTariff $userTariff, Material $material, ?int $widthCm): ?Tariff
    {
        $query = Tariff::query()
            ->where('user_tariff_id', $userTariff->id)
            ->where('material_id', $material->id);

        $allTariffs = $query->get();

        if ($allTariffs->isEmpty()) {
            return null;
        }

        // Только точная ширина
        return $allTariffs->first(fn (Tariff $t) => $t->width !== null && $t->width == $widthCm);
    }

    /**
     * Проверить попадание в диапазон
     */
    private function isInRange(float $value, string $range): bool
    {
        $parts = explode('-', $range);

        $from = (float) trim($parts[0]);
        $to = (float) trim($parts[1]);

        return $value >= $from && $value < $to;
    }

    /**
     * Получить конец диапазона
     */
    private function getRangeEnd(?string $range): float
    {
        if ($range === null) {
            return 0;
        }

        $parts = explode('-', $range);

        return (float) trim($parts[1]);
    }

    /**
     * Создать транзакцию для per_meter
     */
    private function createTransaction(MarketplaceOrderItem $item, array $result, User $user, Carbon $date, bool $test, string $action): void
    {
        if ($test || $result['amount'] == 0) {
            return;
        }

        $isBonus = $result['is_bonus'];
        $widthMeters = ($item->item?->width ?? 0) / 100;

        Transaction::query()->create([
            'user_id' => $user->id,
            'title' => $this->getTitle($item, $isBonus, $action, $widthMeters),
            'amount' => $result['amount'],
            'transaction_type' => 'out',
            'accrual_for_date' => $date->format('Y-m-d'),
            'status' => $isBonus ? 0 : 1,
            'is_bonus' => $isBonus,
        ]);
    }

    /**
     * Создать транзакцию для per_piece
     */
    private function createTransactionForPiece(MarketplaceOrderItem $item, Tariff $tariff, float $amount, User $user, Carbon $date, bool $test, string $action): void
    {
        if ($test || $amount == 0) {
            return;
        }

        $isBonus = $tariff->userTariff->is_bonus;

        Transaction::query()->create([
            'user_id' => $user->id,
            'title' => $this->getTitle($item, $isBonus, $action),
            'amount' => $amount,
            'transaction_type' => 'out',
            'accrual_for_date' => $date->format('Y-m-d'),
            'status' => $isBonus ? 0 : 1,
            'is_bonus' => $isBonus,
        ]);
    }

    /**
     * Заголовок транзакции
     */
    private function getTitle(MarketplaceOrderItem $item, bool $isBonus, string $action, ?float $widthMeters = null): string
    {
        $orderId = $item->marketplaceOrder->order_id;
        $bonusPart = $isBonus ? ' (бонус)' : '';
        $russianAction = UserTariff::getRussianAction($action);

        $title = "ЗП за заказ #{$orderId}{$bonusPart} ({$russianAction})";

        // Добавляем метры только если ширина передана и больше нуля
        if ($widthMeters !== null && $widthMeters > 0) {
            $formattedWidth = $this->formatMeters($widthMeters);
            $title .= " - {$formattedWidth} п.м.";
        }

        return $title;
    }

    /**
     * Форматировать метры для отображения в title
     * Убирает insignificant trailing zeros (2.50 -> 2.5, 2.00 -> 2)
     */
    private function formatMeters(float $meters): string
    {
        $rounded = round($meters, 2);

        if ($rounded == (int) $rounded) {
            return (string) (int) $rounded;
        }

        return (string) $rounded;
    }

    /**
     * Лог начисления с суммами
     */
    private function logAccrual(MarketplaceOrderItem $item, User $user, array $salaryResults, array $bonusResults, float $cumulative, bool $test): void
    {
        $orderId = $item->marketplaceOrder->order_id;
        $width = ($item->item?->width ?? 0) / 100;
        $roleName = $user->role ? UserService::translateRoleName($user->role->name) : 'Сотрудник';

        // Собираем суммы
        $totalSalary = collect($salaryResults)->sum(fn ($r) => $r['amount']);
        $totalBonus = collect($bonusResults)->sum(fn ($r) => $r['amount']);

        // Формируем части лога
        $parts = [];
        if ($totalSalary > 0) {
            $parts[] = "зп {$totalSalary} руб.";
        }
        if ($totalBonus > 0) {
            $parts[] = "бонус {$totalBonus} руб.";
        }

        if (empty($parts)) {
            return;
        }

        $testStr = $test ? ' [ТЕСТ] ' : '';
        $log = "{$testStr}{$roleName}: {$user->name}. Начисляем ".implode(', ', $parts)." за заказ #{$orderId}, ширина: {$width} м. (Кумулятивно: {$cumulative} м.)";

        Log::channel('salary')->info($log);
    }

    /**
     * Начислить оклад за день
     */
    public function accrualSalaryDaily(Carbon $date, bool $test = false): void
    {
        $schedules = Schedule::query()
            ->where('date', $date->format('Y-m-d'))
            ->whereHas('user', fn ($q) => $q->whereNotNull('role_id'))
            ->with('user')
            ->get();

        foreach ($schedules as $schedule) {
            $user = $schedule->user;
            if (! $user) {
                continue;
            }

            $russianAction = 'Оклад';

            // Находим ВСЕ user_tariff для оклада (и ЗП, и бонусы)
            $userTariffs = UserTariff::query()
                ->where('user_id', $user->id)
                ->where('action', $russianAction)
                ->get();

            if ($userTariffs->isEmpty()) {
                Log::channel('salary')->warning(
                    "Не найден тариф оклада для пользователя {$user->name} (id: {$user->id})"
                );

                continue;
            }

            // Собираем результаты начисления
            $salaryResults = [];
            $bonusResults = [];

            foreach ($userTariffs as $userTariff) {
                $tariff = Tariff::query()
                    ->where('user_tariff_id', $userTariff->id)
                    ->first();

                if (! $tariff) {
                    continue;
                }

                $amount = (float) $tariff->value;

                if ($amount > 0) {
                    $result = [
                        'amount' => $amount,
                        'is_bonus' => $userTariff->is_bonus,
                    ];

                    if ($userTariff->is_bonus) {
                        $bonusResults[] = $result;
                    } else {
                        $salaryResults[] = $result;
                    }

                    if (! $test) {
                        Transaction::query()->create([
                            'user_id' => $user->id,
                            'title' => 'Оклад за '.$date->format('d/m/Y').($userTariff->is_bonus ? ' (бонус)' : ''),
                            'amount' => $amount,
                            'transaction_type' => 'out',
                            'accrual_for_date' => $date->format('Y-m-d'),
                            'status' => $userTariff->is_bonus ? 0 : 1,
                            'is_bonus' => $userTariff->is_bonus,
                        ]);
                    }
                }
            }

            // Лог с суммами
            $this->logSalaryDaily($user, $date, $salaryResults, $bonusResults, $test);
        }
    }

    /**
     * Лог начисления оклада
     */
    private function logSalaryDaily(User $user, Carbon $date, array $salaryResults, array $bonusResults, bool $test): void
    {
        $totalSalary = collect($salaryResults)->sum(fn ($r) => $r['amount']);
        $totalBonus = collect($bonusResults)->sum(fn ($r) => $r['amount']);

        if ($totalSalary == 0 && $totalBonus == 0) {
            return;
        }

        $parts = [];
        if ($totalSalary > 0) {
            $parts[] = "зп {$totalSalary} руб.";
        }
        if ($totalBonus > 0) {
            $parts[] = "бонус {$totalBonus} руб.";
        }

        $testStr = $test ? ' [ТЕСТ] ' : '';
        Log::channel('salary')->info(
            "{$testStr}Оклад для {$user->name} (id: {$user->id}): ".implode(', ', $parts)." за {$date->format('d/m/Y')}"
        );
    }
}
