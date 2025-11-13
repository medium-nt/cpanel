<?php

namespace Database\Seeders;

use App\Models\MarketplaceOrderItem;
use Illuminate\Database\Seeder;

class MarketplaceOrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MarketplaceOrderItem::query()->create(
            [
                'marketplace_order_id' => 1,
                'marketplace_item_id' => 1,
                'quantity' => 1,
                'price' => 10.99,
                'status' => 0,
            ]
        );

        MarketplaceOrderItem::query()->create(
            [
                'marketplace_order_id' => 1,
                'marketplace_item_id' => 2,
                'quantity' => 1,
                'price' => 12.00,
                'status' => 0,
            ]
        );

        MarketplaceOrderItem::query()->create(
            [
                'marketplace_order_id' => 2,
                'marketplace_item_id' => 3,
                'quantity' => 3,
                'price' => 10.99,
                'status' => 0,
            ]
        );
    }
}
