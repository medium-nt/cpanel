<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Setting;
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

}
