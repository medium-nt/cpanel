<?php

namespace App\Console\Commands;

use App\Models\MarketplaceItem;
use Illuminate\Console\Command;

class GenerateMarketplaceItemArticles extends Command
{
    protected $signature = 'marketplace_items:generate-articles';

    protected $description = 'Генерация артикулов для товаров по маппингу названий';

    private const TITLE_PREFIX_MAP = [
        'Бамбук' => 'bambuk',
        'Вуаль' => 'vyal',
        'Лен' => 'len',
        'Молния' => 'mol',
        'Мрамор' => 'mramor',
        'Сетка' => 'grek',
        'Шифон' => 'krep',
    ];

    public function handle(): int
    {
        $items = MarketplaceItem::query()->get();
        $updated = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $prefix = self::TITLE_PREFIX_MAP[$item->title] ?? null;

            if ($prefix === null) {
                $this->warn("ПРОПУСК: id={$item->id} title=\"{$item->title}\" — нет маппинга");
                $skipped++;

                continue;
            }

            $item->article = $prefix.($item->width / 100).'_'.$item->height;
            $item->save();
            $this->info("OK: id={$item->id} article={$item->article}");
            $updated++;
        }

        $this->newLine();
        $this->info("Готово: обновлено {$updated}, пропущено {$skipped}");

        return self::SUCCESS;
    }
}
