<?php

namespace Database\Factories;

use App\Models\MarketplaceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarketplaceItem>
 */
class MarketplaceItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MarketplaceItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'width' => $this->faker->randomElement([150, 200, 250, 300]),
            'height' => $this->faker->randomElement([250, 270]),
        ];
    }
}
