<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type_movement' => $this->faker->randomElement([4, 7]),
            'status' => $this->faker->randomElement([0, 1, -1, 3]),
            'supplier_id' => null,
            'storekeeper_id' => User::factory(),
            'seamstress_id' => User::factory(),
            'cutter_id' => User::factory(),
            'comment' => $this->faker->sentence,
            'marketplace_order_id' => null,
            'is_approved' => $this->faker->boolean,
            'completed_at' => null,
        ];
    }
}
