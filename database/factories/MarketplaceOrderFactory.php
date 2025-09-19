<?php

namespace Database\Factories;

use App\Models\MarketplaceOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarketplaceOrder>
 */
class MarketplaceOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MarketplaceOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => $this->faker->unique()->numerify('##########'),
            'marketplace_id' => $this->faker->randomElement([1, 2]), // 1 for OZON, 2 for WB
            'fulfillment_type' => $this->faker->randomElement(['FBS', 'FBO']),
            'created_at' => $this->faker->dateTimeThisMonth(),
        ];
    }
}
