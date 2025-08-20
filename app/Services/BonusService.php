<?php

namespace App\Services;

use App\Models\Bonus;
use Illuminate\Support\Facades\Log;

class BonusService
{
    public static function activateHoldBonus(): void
    {
        Bonus::query()
            ->where('created_at', '<', now()->subDays(30))
            ->where('status', 0)
            ->update([
                'status' => 1]
            );

        Log::channel('erp')
            ->info('Активировали бонусы, по которым прошло более 30 дней');
    }

    public static function addBonus($user, $amount, $type, $title): void
    {
        $status = match ($type) {
            'in' => 0,
            'out' => 1,
        };

        Bonus::query()
            ->create([
                'user_id' => $user->id,
                'title' => $title,
                'amount' => $amount,
                'transaction_type' => $type,
                'status' => $status,
            ]);

        Log::channel('salary')
            ->info('Ручное начисление бонусов в размере ' . $amount . ' рублей ('. $type .') для пользователя ' . $user->name);
    }
}
