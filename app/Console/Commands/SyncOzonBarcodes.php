<?php

namespace App\Console\Commands;

use App\Models\Sku;
use App\Services\MarketplaceApiService;
use Illuminate\Console\Command;

/**
 * Синхронизация штрихкодов OZON FBO с таблицей skus.
 */
class SyncOzonBarcodes extends Command
{
    protected $signature = 'sync:ozon-barcodes';

    protected $description = 'Заполняет колонку barcode в skus для OZON через API';

    /**
     * Обходит OZON SKU без barcode и заполняет их через OZON API.
     */
    public function handle(): int
    {
        $skus = Sku::query()
            ->where('marketplace_id', 1)
            ->whereNull('barcode')
            ->get();

        if ($skus->isEmpty()) {
            $this->info('Нет OZON SKU без barcode — синхронизация не нужна.');

            return self::SUCCESS;
        }

        $this->info("Найдено {$skus->count()} OZON SKU без barcode.");
        $bar = $this->output->createProgressBar($skus->count());
        $bar->start();

        $updated = 0;
        $failed = 0;

        foreach ($skus as $sku) {
            $barcode = MarketplaceApiService::getBarcodeOzonBySku($sku->sku);

            if ($barcode) {
                $sku->update(['barcode' => $barcode]);
                $updated++;
            } else {
                $failed++;
            }

            $bar->advance();

            usleep(100_000); // 100ms между запросами — чтобы не упереться в лимит API
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Готово! Обновлено: {$updated}, не найдено: {$failed}.");

        return self::SUCCESS;
    }
}
