<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;

class ScheduleService
{
    public static function getScheduleByUserId($userId): array
    {
        return Schedule::query()
            ->where('user_id', $userId)
            ->select('id', 'date')
            ->get()
            ->map(function ($event) {
                $event->start = $event->date;
                unset($event->date);
                $event->display = 'background';
                return $event;
            })
            ->toArray();
    }

    public static function isWorkDay(): bool
    {
        $date = Carbon::now()->toDateString();

        $existsDay = Schedule::query()
            ->where('date', $date)
            ->where('user_id', auth()->user()->id)
            ->first();

        if ($existsDay) {
            return true;
        }

        return false;
    }

    public static function isEnabledSchedule(): bool
    {
        return Setting::query()->where('name', 'is_enabled_work_schedule')->first()->value;
    }

    public static function getStartWorkDay()
    {
        return Setting::query()->where('name', 'working_day_start')->first()->value;
    }

    public static function getEndWorkDay()
    {
        return Setting::query()->where('name', 'working_day_end')->first()->value;
    }

    public static function hasWorkDayStarted(): bool
    {
        $nowTime = Carbon::now();

        if (
            $nowTime->lt(Carbon::createFromFormat('H:i', self::getStartWorkDay()))
            || $nowTime->gte(Carbon::createFromFormat('H:i', self::getEndWorkDay()))
        ) {
            return false;
        }

        return true;
    }

    public static function isBeforeStartWorkDay(): bool
    {
        $nowTime = Carbon::now();
        $startWorkDay = Carbon::createFromFormat('H:i', ScheduleService::getStartWorkDay());

        // Проверяем, что сейчас после 01:00 и до начала рабочего дня
        return $nowTime->gte(Carbon::today()->setTime(1, 0)) && $nowTime->lt($startWorkDay);
    }

    public static function openWorkShift(User $user): void
    {
        $schedule = self::getSchedule($user);
        $schedule->shift_opened_time = Carbon::now()->toTimeString();
        $schedule->save();
    }

    public static function closeWorkShift(User $user): void
    {
        $schedule = self::getSchedule($user);
        $schedule->shift_closed_time = Carbon::now()->toTimeString();
        $schedule->save();
    }

    private static function getSchedule(User $user): Schedule
    {
        $today = Carbon::today()->toDateString();

        $schedule = Schedule::query()
            ->where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$schedule) {
            $schedule = new Schedule();
            $schedule->user_id = $user->id;
            $schedule->date = $today;
        }

        return $schedule;
    }

}
