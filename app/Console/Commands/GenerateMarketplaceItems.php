<?php

namespace App\Console\Commands;

use App\Models\MarketplaceItem;
use Illuminate\Console\Command;

class GenerateMarketplaceItems extends Command
{
    protected $signature = 'marketplace_items:generate {title : Название материала}';

    protected $description = 'Генерация товаров для материала по всем сочетаниям ширина × высота';

    private const WIDTHS = [200, 300, 400, 500, 600, 700, 800];

    private const HEIGHTS = [220, 225, 230, 235, 240, 245, 250, 255, 260, 265, 270, 275, 280, 285, 290, 295];

    /**
     * Создаёт товары для указанного материала по всем комбинациям ширина × высота.
     */
    public function handle(): int
    {
        $title = $this->argument('title');

        $this->info("Генерация товаров для материала: \"{$title}\"");

        $existing = MarketplaceItem::query()
            ->where('title', $title)
            ->get(['width', 'height'])
            ->keyBy(fn (MarketplaceItem $item) => $item->width.':'.$item->height);

        $created = 0;
        $skipped = 0;
        $toInsert = [];

        foreach (self::WIDTHS as $width) {
            foreach (self::HEIGHTS as $height) {
                $key = $width.':'.$height;

                if ($existing->has($key)) {
                    $skipped++;

                    continue;
                }

                $toInsert[] = [
                    'title' => $title,
                    'width' => $width,
                    'height' => $height,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $created++;
            }
        }

        if ($toInsert !== []) {
            MarketplaceItem::insert($toInsert);
        }

        $this->newLine();
        $this->info("Готово: создано {$created}, пропущено (дубли) {$skipped}");

        return self::SUCCESS;
    }
}
