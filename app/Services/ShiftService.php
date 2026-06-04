<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use Carbon\Carbon;

class ShiftService
{
    /**
     * Роли, которые работают по сменам (привязаны к бригаде и календарю).
     */
    public const SHIFT_ROLES = ['seamstress', 'cutter', 'otk'];

    /**
     * Получить смену сотрудника на сегодня (по shift_user effective_from).
     */
    public static function getUserShift(User $user): ?Shift
    {
        return $user->currentShift();
    }

    /**
     * Получить сегодняшнюю смену по календарю (shift_schedule).
     */
    public static function getTodayScheduledShift(): ?Shift
    {
        $record = ShiftSchedule::query()
            ->where('date', Carbon::today()->toDateString())
            ->first();

        return $record?->shift;
    }

    /**
     * Проверка: может ли сотрудник работать сегодня.
     *
     * Роли вне смен (кладовщик, водитель, админ) → всегда true.
     * Роли в сменах: совпадает ли смена сотрудника с календарной.
     */
    public static function canWorkToday(User $user): bool
    {
        if (! in_array($user->role?->name, self::SHIFT_ROLES)) {
            return true;
        }

        $userShift = self::getUserShift($user);

        if (! $userShift) {
            return true; // Не привязан к смене — не ограничиваем
        }

        $todayShift = self::getTodayScheduledShift();

        if (! $todayShift) {
            return true; // Нет расписания на сегодня — не ограничиваем
        }

        return $userShift->id === $todayShift->id;
    }

    /**
     * Перевести сотрудника в другую смену.
     */
    public static function transferEmployee(User $user, Shift $newShift, string $effectiveFrom): void
    {
        $user->shifts()->attach($newShift->id, [
            'effective_from' => $effectiveFrom,
        ]);
    }

    /**
     * Получить даты, на которые не заполнено расписание смен.
     *
     * @return string[] Массив дат в формате Y-m-d
     */
    public static function getMissingScheduleDates(int $days = 7): array
    {
        $startDate = Carbon::tomorrow();
        $endDate = Carbon::tomorrow()->addDays($days - 1);

        $existingDates = ShiftSchedule::query()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('date')
            ->map(fn (string $date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $missingDates = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->toDateString();

            if (! in_array($date, $existingDates, true)) {
                $missingDates[] = $date;
            }
        }

        return $missingDates;
    }

    /**
     * Заполнить календарь: массово.
     *
     * @param  array  $data  ['2026-04-08' => shift_id, '2026-04-09' => shift_id, ...]
     */
    public static function fillSchedule(array $data): void
    {
        foreach ($data as $date => $shiftId) {
            ShiftSchedule::query()->updateOrCreate(
                ['date' => $date],
                ['shift_id' => $shiftId],
            );
        }
    }
}
