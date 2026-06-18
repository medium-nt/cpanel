<?php

namespace Database\Seeders;

use App\Models\ProductSticker;
use Illuminate\Database\Seeder;

class ProductStickerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт 5-10 вариантов наклеек
     */
    public function run(): void
    {
        $stickers = [
            [
                'title' => 'Наклейка белая матовая',
                'color' => 'Белый',
                'print_type' => 'Термотрансфер',
                'material' => 'Бумага матовая',
                'country' => 'Россия',
                'fastening_type' => 'Самоклейкаяся',
            ],
            [
                'title' => 'Наклейка прозрачная',
                'color' => 'Прозрачный',
                'print_type' => 'Термотрансфер',
                'material' => 'Пленка ПВХ',
                'country' => 'Россия',
                'fastening_type' => 'Самоклейкаяся',
            ],
            [
                'title' => 'Наклейка золотая',
                'color' => 'Золотой',
                'print_type' => 'Шелкография',
                'material' => 'Бумага глянцевая',
                'country' => 'Китай',
                'fastening_type' => 'Самоклейкаяся',
            ],
            [
                'title' => 'Наклейка серебристая',
                'color' => 'Серебристый',
                'print_type' => 'Термотрансфер',
                'material' => 'Пленка металлизированная',
                'country' => 'Россия',
                'fastening_type' => 'Самоклейкаяся',
            ],
            [
                'title' => 'Этикетка хлопок',
                'color' => 'Белый',
                'print_type' => 'Термотрансфер',
                'material' => 'Хлопок',
                'country' => 'Турция',
                'fastening_type' => 'Пришивная',
            ],
            [
                'title' => 'Этикетка лен',
                'color' => 'Натуральный',
                'print_type' => 'Шелкография',
                'material' => 'Лён',
                'country' => 'Россия',
                'fastening_type' => 'Пришивная',
            ],
            [
                'title' => 'Наклейка черная',
                'color' => 'Черный',
                'print_type' => 'Термотрансфер',
                'material' => 'Бумага матовая',
                'country' => 'Россия',
                'fastening_type' => 'Самоклейкаяся',
            ],
            [
                'title' => 'Бирка кожаная',
                'color' => 'Коричневый',
                'print_type' => 'Гравировка',
                'material' => 'Кожа',
                'country' => 'Италия',
                'fastening_type' => 'Пришивная',
            ],
        ];

        foreach ($stickers as $sticker) {
            ProductSticker::query()->firstOrCreate(
                ['title' => $sticker['title']],
                $sticker
            );
        }

        $this->command->info('Создано наклеек: '.count($stickers));
    }
}
