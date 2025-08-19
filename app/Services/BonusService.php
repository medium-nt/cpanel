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
}
