<?php

namespace Database\Factories;

use App\Models\MarketplaceItem;
use App\Models\Sku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sku>
 */
class SkuFactory extends Factory
{
    protected $model = Sku::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => MarketplaceItem::factory(),
            'sku' => $this->faker->unique()->numerify('SKU-#####'),
            'marketplace_id' => $this->faker->numberBetween(1, 2),
        ];
    }
}