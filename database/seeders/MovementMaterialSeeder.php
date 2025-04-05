<?php

namespace Database\Seeders;

use App\Models\MovementMaterial;
use Illuminate\Database\Seeder;

class MovementMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MovementMaterial::query()->create(
            [
                'material_id' => 1,
                'quantity' => 100,
                'comment' => 'тестовая поставка',
                'order_id' => 1
            ]
        );

        MovementMaterial::query()->create(
            [
                'material_id' => 2,
                'quantity' => 50,
                'comment' => 'тестовая поставка',
                'order_id' => 1
            ]
        );

        MovementMaterial::query()->create(
            [
                'material_id' => 3,
                'quantity' => 10,
                'comment' => 'тестовая поставка',
                'order_id' => 1
            ]
        );

        MovementMaterial::query()->create(
            [
                'material_id' => 1,
                'quantity' => 0,
                'ordered_quantity' => 20,
                'comment' => 'тестовый заказ',
                'order_id' => 2
            ]
        );
    }
}
