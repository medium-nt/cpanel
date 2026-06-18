<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт 4 смены (по 2 на каждый цех)
     */
    public function run(): void
    {
        Shift::query()->firstOrCreate(
            ['workshop_id' => 1, 'name' => 'Первая смена (Цех №1)'],
            ['status' => Shift::STATUS_ACTIVE]
        );

        Shift::query()->firstOrCreate(
            ['workshop_id' => 1, 'name' => 'Вторая смена  (Цех №1)'],
            ['status' => Shift::STATUS_ACTIVE]
        );

        Shift::query()->firstOrCreate(
            ['workshop_id' => 2, 'name' => 'Третья смена  (Цех №2)'],
            ['status' => Shift::STATUS_ACTIVE]
        );

        Shift::query()->firstOrCreate(
            ['workshop_id' => 2, 'name' => 'Четвертая смена  (Цех №2)'],
            ['status' => Shift::STATUS_ACTIVE]
        );
    }
}
