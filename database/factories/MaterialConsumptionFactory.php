<?php

namespace Database\Factories;

use App\Models\MarketplaceItem;
use App\Models\Material;
use App\Models\MaterialConsumption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaterialConsumption>
 */
class MaterialConsumptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MaterialConsumption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => MarketplaceItem::factory(),
            'material_id' => Material::factory(),
            'quantity' => $this->faker->randomFloat(2, 0.1, 10),
        ];
    }

    /**
     * Create a consumption for specific item
     */
    public function forItem(MarketplaceItem $item): static
    {
        return $this->state(fn (array $attributes) => [
            'item_id' => $item->id,
        ]);
    }

    /**
     * Create a consumption for specific material
     */
    public function forMaterial(Material $material): static
    {
        return $this->state(fn (array $attributes) => [
            'material_id' => $material->id,
        ]);
    }

    /**
     * Create a consumption with specific quantity
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }
}
