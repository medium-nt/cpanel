<?php

namespace App\Services;

use App\Models\MarketplaceSupply;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MarketplaceSupplyService
{
    public static function deleteOldVideos(): void
    {
        Log::channel('erp')
            ->info('    Запускаем ежедневную очистку старых видео...');

        $video = MarketplaceSupply::query()
            ->where('video', '!=', null)
            ->where('created_at', '<', now()->subDays(60))
            ->get();

        foreach ($video as $item) {
            if (Storage::disk('public')->exists('videos/' . $item->video)) {
                Storage::disk('public')->delete('videos/' . $item->video);
            }

            $item->update([
                'video' => null
            ]);

            Log::channel('erp')
                ->notice('    видео для поставки #' . $item->id . ' удалено после 60 дней.');
        }

        Log::channel('erp')
            ->info('    Очистка старых видео завершена.');
    }

}
