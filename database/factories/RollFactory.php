<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Roll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для создания рулонов в тестах.
 */
class RollFactory extends Factory
{
    protected $model = Roll::class;

    public function definition(): array
    {
        return [
            'shift_id' => null,
            'roll_code' => $this->faker->unique()->bothify('R-####'),
            'material_id' => Material::factory(),
            'status' => Roll::STATUS_IN_STORAGE,
            'initial_quantity' => $this->faker->numberBetween(10, 200),
            'shortage_quantity' => 0,
            'is_printed' => false,
            'completed_at' => null,
            'completed_by' => null,
        ];
    }

    public function inStorage(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Roll::STATUS_IN_STORAGE,
        ]);
    }

    public function shippedToWorkshop(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Roll::STATUS_SHIPPED_TO_WORKSHOP,
        ]);
    }

    public function inWorkshop(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Roll::STATUS_IN_WORKSHOP,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Roll::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
