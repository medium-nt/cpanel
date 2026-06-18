<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemWorkshopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Связывает товары с цехами
     */
    public function run(): void
    {
        // Получаем ID товаров
        $itemIds = DB::table('marketplace_items')->limit(15)->pluck('id');

        // Получаем ID цехов
        $workshopIds = DB::table('workshops')->pluck('id');

        if ($itemIds->isEmpty() || $workshopIds->isEmpty()) {
            $this->command->warn('Недостаточно данных для связывания товаров с цехами.');

            return;
        }

        $linksCount = 0;

        // Связываем товары с цехами
        foreach ($itemIds as $itemId) {
            // Каждый товар доступен в 1-2 цехах
            $workshopsCount = rand(1, 2);

            for ($i = 0; $i < $workshopsCount; $i++) {
                $workshopId = $workshopIds[$i % $workshopIds->count()];

                // Проверяем существует ли связь
                $exists = DB::table('item_workshop')
                    ->where('marketplace_item_id', $itemId)
                    ->where('workshop_id', $workshopId)
                    ->exists();

                if (! $exists) {
                    DB::table('item_workshop')->insert([
                        'marketplace_item_id' => $itemId,
                        'workshop_id' => $workshopId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $linksCount++;
                }
            }
        }

        $this->command->info("Создано связей товар-цех: {$linksCount}");
    }
}
