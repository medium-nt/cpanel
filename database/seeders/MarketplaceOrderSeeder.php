<?php

namespace Database\Seeders;

use App\Models\MarketplaceOrder;
use Illuminate\Database\Seeder;

class MarketplaceOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MarketplaceOrder::query()->create(
            [
                'status' => 0,
                'order_id' => 'A-12345',
                'marketplace_id' => 1,
                'fulfillment_type' => 'FBO',
            ]
        );

        MarketplaceOrder::query()->create(
            [
                'status' => 1,
                'order_id' => 'WB-12-ZZ-123',
                'marketplace_id' => 2,
                'fulfillment_type' => 'FBS',
            ]
        );
    }
}
