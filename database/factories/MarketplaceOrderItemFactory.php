<?php

namespace Database\Factories;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarketplaceOrderItem>
 */
class MarketplaceOrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MarketplaceOrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'marketplace_order_id' => MarketplaceOrder::factory(),
            'marketplace_item_id' => MarketplaceItem::factory(),
            'quantity' => $this->faker->numberBetween(1, 5),
            'status' => 0, // new
            'seamstress_id' => null,
            'cutter_id' => null,
            'completed_at' => null,
            'cutting_completed_at' => null,
        ];
    }
}
