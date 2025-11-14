<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MovementMaterial>
 */
class MovementMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'material_id' => Material::factory(),
            'order_id' => Order::factory(),
            'quantity' => $this->faker->numberBetween(1, 100),
            'ordered_quantity' => $this->faker->numberBetween(1, 100),
            'price' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
