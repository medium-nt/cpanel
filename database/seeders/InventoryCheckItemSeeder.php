<?php

namespace Database\Seeders;

use App\Models\InventoryCheckItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryCheckItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт 5-10 позиций инвентаризации
     */
    public function run(): void
    {
        // Получаем ID инвентаризаций
        $checkIds = DB::table('inventory_checks')->pluck('id');

        // Получаем ID товаров
        $itemIds = DB::table('marketplace_order_items')
            ->limit(10)
            ->pluck('id');

        // Получаем ID полок
        $shelfIds = DB::table('shelves')->pluck('id');

        if ($checkIds->isEmpty() || $itemIds->isEmpty() || $shelfIds->isEmpty()) {
            $this->command->warn('Недостаточно данных для создания позиций инвентаризации.');

            return;
        }

        $itemsCount = 0;

        // Распределяем товары по инвентаризациям
        foreach ($checkIds as $checkId) {
            // 2-4 товара на каждую инвентаризацию
            $itemsForCheck = rand(2, 4);

            for ($i = 0; $i < $itemsForCheck && $itemsCount < $itemIds->count(); $i++) {
                $isFound = rand(0, 1);

                $checkItemData = [
                    'inventory_check_id' => $checkId,
                    'marketplace_order_item_id' => $itemIds[$itemsCount],
                    'expected_shelf_id' => $shelfIds->random(),
                    'is_found' => $isFound,
                    'is_added_later' => rand(0, 1),
                ];

                // Если найден, указываем где
                if ($isFound) {
                    $checkItemData['founded_shelf_id'] = $shelfIds->random();
                }

                // Используем firstOrCreate чтобы избежать дубликатов
                InventoryCheckItem::query()->firstOrCreate(
                    [
                        'inventory_check_id' => $checkItemData['inventory_check_id'],
                        'marketplace_order_item_id' => $checkItemData['marketplace_order_item_id'],
                    ],
                    $checkItemData
                );
                $itemsCount++;
            }
        }

        $this->command->info("Создано позиций инвентаризации: {$itemsCount}");
    }
}
