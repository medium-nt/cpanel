<?php

namespace Database\Seeders;

use App\Models\Tariff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TariffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт детальные тарифы (5-10)
     */
    public function run(): void
    {
        // Получаем ID пользовательских тарифов
        $userTariffIds = DB::table('user_tariffs')->limit(5)->pluck('id');

        // Получаем ID материалов
        $materialIds = DB::table('materials')->limit(5)->pluck('id');

        if ($userTariffIds->isEmpty() || $materialIds->isEmpty()) {
            $this->command->warn('Недостаточно данных для создания тарифов. Сначала создайте user_tariffs.');

            return;
        }

        $tariffsCount = 0;
        $ranges = ['0-3', '3-9', '9-18', '18-35', '35-50'];
        $widths = [200, 300, 400, 500, null];

        // Создаём 10 тарифов
        for ($i = 0; $i < 10; $i++) {
            Tariff::query()->create([
                'user_tariff_id' => $userTariffIds->random(),
                'material_id' => $materialIds->random(),
                'range' => $ranges[array_rand($ranges)],
                'width' => $widths[array_rand($widths)],
                'value' => rand(100, 500) / 100, // 1.00 - 5.00
            ]);
            $tariffsCount++;
        }

        $this->command->info("Создано тарифов: {$tariffsCount}");
    }
}
