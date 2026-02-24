<?php

namespace App\Services;

use App\Models\MarketplaceSupply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MarketplaceSupplyService
{
    public static function deleteOldVideos(): void
    {
        Log::channel('erp')
            ->info('Запускаем ежедневную очистку старых видео...');

        $video = MarketplaceSupply::query()
            ->where('video', '!=', null)
            ->where('created_at', '<', now()->subDays(60))
            ->get();

        foreach ($video as $item) {
            if (Storage::disk('public')->exists('videos/'.$item->video)) {
                Storage::disk('public')->delete('videos/'.$item->video);
            }

            $item->update([
                'video' => null,
            ]);

            Log::channel('erp')
                ->notice('Видео для поставки #'.$item->id.' удалено после 60 дней.');
        }

        Log::channel('erp')
            ->info('Очистка старых видео завершена.');
    }

    public static function chunkedUpload(Request $request): JsonResponse
    {
        $file = $request->file('video');
        $index = $request->get('dzchunkindex');
        $totalChunks = $request->get('dztotalchunkcount');
        $uuid = $request->get('dzuuid');
        $marketplaceSupplyId = $request->get('marketplace_supply_id');

        $marketplaceSupply = MarketplaceSupply::find($marketplaceSupplyId);

        Log::info("Чанк #{$index} / {$totalChunks} получен.");

        $fileName = $request->get('marketplace_supply_id').'.'.$file->getClientOriginalExtension();
        $chunkPath = "chunks/{$uuid}";
        $chunkName = "{$index}.part";

        Storage::putFileAs($chunkPath, $file, $chunkName);

        $savedChunks = Storage::files($chunkPath);

        if (count($savedChunks) == $totalChunks) {
            Log::info('Все чанки получены. Начинаем сборку...');

            $finalPath = "videos/{$fileName}";
            $stream = fopen('php://temp', 'w+b');

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkFile = "{$chunkPath}/{$i}.part";
                if (Storage::exists($chunkFile)) {
                    $chunkStream = Storage::readStream($chunkFile);
                    stream_copy_to_stream($chunkStream, $stream);
                    fclose($chunkStream);
                } else {
                    Log::warning("Пропущен чанк: {$chunkFile}");
                }
            }

            rewind($stream);
            Storage::disk('public')->put($finalPath, $stream);
            fclose($stream);

            Log::info("Видео собрано и сохранено: {$finalPath}");

            $marketplaceSupply->update([
                'video' => $fileName,
            ]);

            if (Storage::exists($chunkPath)) {
                Storage::deleteDirectory($chunkPath);
                Log::info("Удалены чанки: {$chunkPath}");
            }

            return response()->json([
                'status' => 'Видео загружено и собрано',
                'filename' => $fileName,
            ]);
        }

        return response()->json([
            'status' => "Чанк {$index} сохранён",
        ]);
    }

    public static function updateStatusSupply(): void
    {
        Log::channel('erp')->info('Запуск обновления статусов поставок...');

        $supplies = MarketplaceSupply::query()
            ->where('status', 4)
            ->get();

        $count = 0;
        foreach ($supplies as $supply) {
            $isUpdated = match ($supply->marketplace_id) {
                1 => MarketplaceApiService::updateStatusOrderBySupplyOzon($supply),
                2 => MarketplaceApiService::updateStatusOrderBySupplyWB($supply),
            };

            if (! $isUpdated) {
                Log::channel('erp')
                    ->error('Не удалось обновить статус поставки #'.$supply->id);

                continue;
            }

            $hasOldStatuses = $supply->marketplace_orders()
                ->whereIn('marketplace_status', ['confirm', 'awaiting_deliver'])
                ->exists();

            if (! $hasOldStatuses) {
                $supply->update([
                    'status' => 3,
                    'completed_at' => now(),
                ]);

                Log::channel('erp')
                    ->info('Поставка #'.$supply->id.' закрыта, так как у всех товаров новый статус.');
            }

            $count++;
        }

        Log::channel('erp')->info("Обновление статусов поставок завершено. Обработано: {$count}");
    }
}
