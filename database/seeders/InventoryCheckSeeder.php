<?php

namespace Database\Seeders;

use App\Models\InventoryCheck;
use Illuminate\Database\Seeder;

class InventoryCheckSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт 2-3 инвентаризации
     */
    public function run(): void
    {
        // Инвентаризация #1 - открыта
        InventoryCheck::query()->create([
            'status' => 'in_progress',
            'comment' => 'Ежемесячная инвентаризация Цеха №1',
        ]);

        // Инвентаризация #2 - открыта
        InventoryCheck::query()->create([
            'status' => 'in_progress',
            'comment' => 'Проверка остатков на складе',
        ]);

        // Инвентаризация #3 - завершена
        InventoryCheck::query()->create([
            'status' => 'closed',
            'comment' => 'Квартальная инвентаризация',
            'finished_at' => now()->subDays(7),
        ]);
    }
}
