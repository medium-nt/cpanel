<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialWorkshopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Связывает материалы с цехами
     */
    public function run(): void
    {
        // Получаем ID материалов
        $materialIds = DB::table('materials')->limit(10)->pluck('id');

        // Получаем ID цехов
        $workshopIds = DB::table('workshops')->pluck('id');

        if ($materialIds->isEmpty() || $workshopIds->isEmpty()) {
            $this->command->warn('Недостаточно данных для связывания материалов с цехами.');

            return;
        }

        $linksCount = 0;

        // Связываем материалы с цехами
        foreach ($materialIds as $materialId) {
            // Каждый материал доступен в 1-2 цехах
            $workshopsCount = rand(1, 2);

            for ($i = 0; $i < $workshopsCount; $i++) {
                $workshopId = $workshopIds[$i % $workshopIds->count()];

                // Проверяем существует ли связь
                $exists = DB::table('material_workshop')
                    ->where('material_id', $materialId)
                    ->where('workshop_id', $workshopId)
                    ->exists();

                if (! $exists) {
                    DB::table('material_workshop')->insert([
                        'material_id' => $materialId,
                        'workshop_id' => $workshopId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $linksCount++;
                }
            }
        }

        $this->command->info("Создано связей материал-цех: {$linksCount}");
    }
}
