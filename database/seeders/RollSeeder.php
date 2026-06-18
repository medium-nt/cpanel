<?php

namespace Database\Seeders;

use App\Models\Roll;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт 20-30 рулонов материалов в разных статусах
     */
    public function run(): void
    {
        // Получаем ID смен
        $shiftIds = DB::table('shifts')->pluck('id');

        // Получаем ID материалов
        $materialIds = DB::table('materials')->pluck('id');

        if ($shiftIds->isEmpty() || $materialIds->isEmpty()) {
            $this->command->warn('Недостаточно данных для создания рулонов. Сначала запустите ShiftSeeder и MaterialSeeder.');

            return;
        }

        $statuses = [
            Roll::STATUS_IN_STORAGE,
            Roll::STATUS_SHIPPED_TO_WORKSHOP,
            Roll::STATUS_IN_WORKSHOP,
            Roll::STATUS_COMPLETED,
        ];

        // Создаём 25 рулонов
        $rollsCount = 25;

        for ($i = 1; $i <= $rollsCount; $i++) {
            $status = $statuses[array_rand($statuses)];
            $initialQuantity = rand(100, 500) / 10; // 10.0 - 50.0

            $rollData = [
                'shift_id' => $shiftIds->random(),
                'material_id' => $materialIds->random(),
                'roll_code' => 'ROLL-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'status' => $status,
                'initial_quantity' => $initialQuantity,
                'shortage_quantity' => rand(0, 10) / 10, // 0 - 1.0
                'is_printed' => rand(0, 1),
            ];

            // Если статус completed, устанавливаем completed_at
            if ($status === Roll::STATUS_COMPLETED) {
                $rollData['completed_at'] = now()->subDays(rand(1, 30));
                $rollData['completed_by'] = rand(1, 5); // Предполагаем что есть пользователи с ID 1-5
            }

            Roll::query()->create($rollData);
        }

        $this->command->info("Создано рулонов: {$rollsCount}");
    }
}
