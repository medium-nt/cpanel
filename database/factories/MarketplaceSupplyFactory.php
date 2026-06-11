<?php

namespace Database\Factories;

use App\Models\MarketplaceSupply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarketplaceSupply>
 */
class MarketplaceSupplyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MarketplaceSupply::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supply_id' => $this->faker->unique()->numerify('#######'),
            'marketplace_id' => $this->faker->randomElement([1, 2]),
            'type' => 'FBS',
            'status' => 0,
        ];
    }
}
