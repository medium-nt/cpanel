<?php

namespace Database\Factories;

use App\Models\MarketplaceSupply;
use App\Models\SupplyBox;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupplyBox>
 */
class SupplyBoxFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SupplyBox::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'marketplace_supply_id' => MarketplaceSupply::factory(),
            'number' => $this->faker->unique()->bothify('BOX-####'),
            'closed_at' => null,
        ];
    }
}
