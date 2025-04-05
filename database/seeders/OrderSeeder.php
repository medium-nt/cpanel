<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Order::query()->create([
            'type_movement' => 1,
            'status_movement' => 3,
            'supplier_id' => 1,
            'storekeeper_id' => 2,
            'completed_at' => now()
        ]);

        Order::query()->create([
            'type_movement' => 2,
            'status_movement' => 0,
            'supplier_id' => 1,
            'seamstress_id' => 3
        ]);
    }
}
