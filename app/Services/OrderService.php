<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class OrderService
{
    public static function getFiltered($request): Builder
    {
        $statusId = [0, 1];
        match ($request->status) {
            '-1' => $statusId = [-1],
            '3' => $statusId = [3],
            default => $statusId,
        };

        $orders = Order::query()
            ->whereIn('status', $statusId);

        if ($request->has('type_movement')) {
            $orders = $orders->where('type_movement', $request->type_movement);
        } else {
            $orders = $orders->whereIn('type_movement', [4, 7]);
        }

        if ($request->has('users_id')) {
            $orders = $orders->where(function ($query) use ($request) {
                $query->where('seamstress_id', $request->users_id)
                    ->orWhere('cutter_id', $request->users_id);
            });
        }

        if ($request->has('date_start')) {
            $orders = $orders->where('created_at', '>=', $request->date_start);
        }

        $dateEndWithTime = Carbon::parse($request->date_end)->endOfDay();
        if ($request->has('date_end')) {
            $orders = $orders->where('created_at', '<=', $dateEndWithTime);
        }

        return $orders->latest();
    }
}
