<?php

namespace Database\Seeders;

use App\Models\SupplyBox;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplyBoxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт 2-3 короба для каждой FBO поставки
     */
    public function run(): void
    {
        // Получаем все FBO поставки
        $fboSupplies = DB::table('marketplace_supplies')
            ->where('type', 'FBO')
            ->pluck('id');

        foreach ($fboSupplies as $supplyId) {
            // Создаём 2-3 короба для каждой FBO поставки
            $boxesCount = rand(2, 3);

            for ($i = 1; $i <= $boxesCount; $i++) {
                // Передаём пустой number, модель заполнит его в created-событии
                $box = SupplyBox::query()->create([
                    'marketplace_supply_id' => $supplyId,
                    'number' => '', // Будет заполнено в событии created модели
                    'closed_at' => $i === $boxesCount ? now() : null, // Последний короб закрыт
                ]);
            }
        }

        $this->command->info('Создано коробов: '.SupplyBox::query()->count());
    }
}
