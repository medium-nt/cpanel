<?php

namespace Database\Seeders;

use App\Models\Motivation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MotivationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт мотивацию для сотрудников (2-3 схемы на каждого)
     */
    public function run(): void
    {
        // Получаем ID сотрудников
        $userIds = DB::table('users')
            ->whereIn('role_id', function ($query) {
                $query->select('id')
                    ->from('roles')
                    ->whereIn('name', ['seamstress', 'cutter', 'otk']);
            })
            ->limit(5)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            $this->command->warn('Недостаточно данных для создания мотивации.');

            return;
        }

        $motivationsCount = 0;

        foreach ($userIds as $userId) {
            // 2-3 схемы мотивации на каждого сотрудника
            $schemesCount = rand(2, 3);

            // Схема 1: 0-10 изделий
            Motivation::query()->firstOrCreate(
                ['user_id' => $userId, 'from' => 0, 'to' => 10],
                [
                    'bonus' => 0,
                    'not_cutter_bonus' => 20,
                    'cutter_bonus' => 0,
                ]
            );
            $motivationsCount++;

            // Схема 2: 10-50 изделий
            Motivation::query()->firstOrCreate(
                ['user_id' => $userId, 'from' => 10, 'to' => 50],
                [
                    'bonus' => 2,
                    'not_cutter_bonus' => 30,
                    'cutter_bonus' => 3,
                ]
            );
            $motivationsCount++;

            // Схема 3: 50+ изделий (если rand == 3)
            if ($schemesCount === 3) {
                Motivation::query()->firstOrCreate(
                    ['user_id' => $userId, 'from' => 50, 'to' => 89],
                    [
                        'bonus' => 5,
                        'not_cutter_bonus' => 40,
                        'cutter_bonus' => 4,
                    ]
                );
                $motivationsCount++;
            }
        }

        $this->command->info("Создано схем мотивации: {$motivationsCount}");
    }
}
