<?php

namespace Database\Factories;

use App\Models\Rate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rate>
 */
class RateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Rate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'width' => $this->faker->numberBetween(100, 300),
            'rate' => $this->faker->numberBetween(100, 500),
            'cutter_rate' => $this->faker->numberBetween(50, 250),
            'not_cutter_rate' => $this->faker->numberBetween(100, 500),
        ];
    }
}
