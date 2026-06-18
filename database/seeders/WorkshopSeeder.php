<?php

namespace Database\Seeders;

use App\Models\Workshop;
use Illuminate\Database\Seeder;

class WorkshopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт 2 цеха для производства
     */
    public function run(): void
    {
        Workshop::query()->firstOrCreate(
            ['title' => 'Цех №1'],
            ['status' => Workshop::STATUS_ACTIVE]
        );

        Workshop::query()->firstOrCreate(
            ['title' => 'Цех №2'],
            ['status' => Workshop::STATUS_ACTIVE]
        );
    }
}
