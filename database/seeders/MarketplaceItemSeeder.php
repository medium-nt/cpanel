<?php

namespace Database\Seeders;

use App\Models\MarketplaceItem;
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

        foreach ($fabrics as $fabric) {
            foreach ($widths as $width) {
                foreach ($heights as $height) {
                    // Генерируем уникальный SKU
                    $sku = substr(md5(rand()), 0, 9);

                    MarketplaceItem::query()->create([
                        'sku' => $sku,
                        'title' => $fabric,
                        'width' => $width,
                        'height' => $height,
                        'marketplace_id' => rand(1, 2), // случайный marketplace_id
                    ]);
                }
            }
        }
    }
}
