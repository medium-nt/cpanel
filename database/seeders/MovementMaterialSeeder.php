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
                'ordered_quantity' => 0,
                'price' => 0,
                'comment' => 'тестовая поставка',
                'type_movement' => 1,
                'status_movement' => 1,
                'supplier_id' => 1,
                'storekeeper_id' => 2,
                'completed_at' => now()
            ]
        );

        MovementMaterial::query()->create(
            [
                'material_id' => 2,
                'quantity' => 50,
                'ordered_quantity' => 0,
                'price' => 0,
                'comment' => 'тестовая поставка',
                'type_movement' => 1,
                'status_movement' => 1,
                'supplier_id' => 1,
                'storekeeper_id' => 2,
                'completed_at' => now()
            ]
        );

        MovementMaterial::query()->create(
            [
                'material_id' => 3,
                'quantity' => 10,
                'ordered_quantity' => 0,
                'price' => 0,
                'comment' => 'тестовая поставка',
                'type_movement' => 1,
                'status_movement' => 1,
                'supplier_id' => 1,
                'storekeeper_id' => 2,
                'completed_at' => now()
            ]
        );

        MovementMaterial::query()->create(
            [
                'material_id' => 1,
                'quantity' => 0,
                'ordered_quantity' => 20,
                'comment' => 'тестовый заказ',
                'type_movement' => 2,
                'status_movement' => 0,
                'order_id' => 1,
                'seamstress_id' => 3,
            ]
        );
    }
}
