<?php

namespace Database\Factories;

use App\Models\Motivation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Motivation>
 */
class MotivationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Motivation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'from' => $this->faker->numberBetween(0, 10),
            'to' => $this->faker->numberBetween(11, 20),
            'bonus' => $this->faker->numberBetween(10, 50),
            'cutter_bonus' => $this->faker->numberBetween(10, 50),
            'not_cutter_bonus' => $this->faker->numberBetween(10, 50),
        ];
    }
}
