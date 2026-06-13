<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'name' => $this->faker->unique()->numerify('Shift-####'),
            'status' => Shift::STATUS_ACTIVE,
        ];
    }

    /**
     * Indicate that the shift is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Shift::STATUS_INACTIVE,
        ]);
    }
}
