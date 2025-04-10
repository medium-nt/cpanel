<?php

namespace Database\Seeders;

use App\Models\MarketplaceItem;
use App\Models\Sku;
use Illuminate\Database\Seeder;

class MarketplaceItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Массивы с возможными значениями ширины и высоты
        $widths = [200, 300, 400, 500, 600, 700, 800];
        $heights = [220, 225, 230, 235, 240, 245, 250, 255, 260, 265, 270, 275, 280, 285, 290, 295];

        // Массив с названиями тканей
        $fabrics = ['Бамбук', 'Сетка', 'Лен', 'Вуаль', 'Шифон'];

        // Массив с id маркетплейсов
        $marketplaceItems = [1, 2];

        foreach ($fabrics as $fabric) {
            foreach ($widths as $width) {
                foreach ($heights as $height) {

                    $sku = substr(md5(rand()), 0, 9);

                    $item = MarketplaceItem::query()->create([
                        'title' => $fabric,
                        'width' => $width,
                        'height' => $height
                    ]);

                    foreach ($marketplaceItems as $marketplaceItem) {
                        Sku::query()->create([
                            'item_id' => $item->id,
                            'sku' => $sku,
                            'marketplace_id' => $marketplaceItem
                        ]);
                    }
                }
            }
        }
    }
}
