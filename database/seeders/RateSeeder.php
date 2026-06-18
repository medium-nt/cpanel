<?php

namespace Database\Seeders;

use App\Models\Rate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт ставки для сотрудников (2-3 материала на каждого)
     */
    public function run(): void
    {
        // Получаем ID сотрудников (швеи, закройщики, ОТК, кладовщики, упаковщики)
        $userIds = DB::table('users')
            ->whereIn('role_id', function ($query) {
                $query->select('id')
                    ->from('roles')
                    ->whereIn('name', ['seamstress', 'cutter', 'otk', 'storekeeper', 'repacker']);
            })
            ->limit(5)
            ->pluck('id');

        // Получаем ID материалов
        $materialIds = DB::table('materials')->limit(5)->pluck('id');

        if ($userIds->isEmpty() || $materialIds->isEmpty()) {
            $this->command->warn('Недостаточно данных для создания ставок.');

            return;
        }

        $ratesCount = 0;

        foreach ($userIds as $userId) {
            // 2-3 материала на каждого сотрудника
            $materialsCount = rand(2, 3);

            for ($i = 0; $i < $materialsCount; $i++) {
                $materialId = $materialIds[$i];

                Rate::query()->firstOrCreate(
                    ['user_id' => $userId, 'material_id' => $materialId],
                    [
                        'rate' => rand(10, 50),           // Базовая ставка
                        'not_cutter_rate' => rand(20, 80), // Ставка для некроивщиков
                        'cutter_rate' => rand(5, 25),    // Ставка для кривщиков
                    ]
                );
                $ratesCount++;
            }
        }

        $this->command->info("Создано ставок: {$ratesCount}");
    }
}
