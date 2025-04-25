<?php

namespace App\Services;

use App\Models\Schedule;

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


}
