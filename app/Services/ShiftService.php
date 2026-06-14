<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     * Получить все смены, запланированные на сегодня (по всем цехам).
     */
    public static function getTodayScheduledShifts(): Collection
    {
        return ShiftSchedule::query()
            ->where('date', Carbon::today()->toDateString())
            ->with('shift')
            ->get()
            ->pluck('shift')
            ->filter();
    }

    /**
     * Проверка: может ли сотрудник работать сегодня.
     *
     * Роли вне смен (кладовщик, водитель, админ) → всегда true.
     * Роли в сменах: совпадает ли смена сотрудника с одной из запланированных.
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

        // Выходной для цеха сотрудника — работать нельзя.
        $isDayOff = ShiftSchedule::query()
            ->where('date', Carbon::today()->toDateString())
            ->where('workshop_id', $userShift->workshop_id)
            ->whereNull('shift_id')
            ->exists();

        if ($isDayOff) {
            return false;
        }

        $todayShifts = self::getTodayScheduledShifts();

        if ($todayShifts->isEmpty()) {
            return true; // Нет расписания на сегодня — не ограничиваем
        }

        return $todayShifts->contains('id', $userShift->id);
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
     * Днём считается заполненным, если есть запись в календаре (рабочая смена
     * либо явно отмеченный выходной). Missing — день без записи вовсе.
     *
     * @return string[] Массив дат в формате Y-m-d
     */
    public static function getMissingScheduleDates(int $days = 7, ?int $workshopId = null): array
    {
        $startDate = Carbon::tomorrow();
        $endDate = Carbon::tomorrow()->addDays($days - 1);

        $query = ShiftSchedule::query()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($workshopId !== null) {
            $query->where('workshop_id', $workshopId);
        }

        $existingDates = $query
            ->pluck('date')
            ->map(fn (string $date) => Carbon::parse($date)->toDateString())
            ->unique()
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
     * Заполнить календарь цеха: массово, одна запись на (workshop_id, date).
     *
     * @param  array  $data  ['2026-04-08' => 'day_off'|shift_id, ...]; отсутствующие даты не трогаются.
     * @param  int  $workshopId  Цех, для которого заполняется календарь.
     */
    public static function fillSchedule(array $data, int $workshopId): void
    {
        foreach ($data as $date => $value) {
            if ($value === 'day_off') {
                ShiftSchedule::query()->updateOrCreate(
                    ['workshop_id' => $workshopId, 'date' => $date],
                    ['shift_id' => null, 'workshop_id' => $workshopId],
                );
            } elseif ($value) {
                ShiftSchedule::query()->updateOrCreate(
                    ['workshop_id' => $workshopId, 'date' => $date],
                    ['shift_id' => $value, 'workshop_id' => $workshopId],
                );
            }
        }
    }
}
