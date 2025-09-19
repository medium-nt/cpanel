<?php

namespace Database\Factories;

use App\Models\Stack;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stack>
 */
class StackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Stack::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seamstress_id' => User::factory(),
            'stack' => $this->faker->numberBetween(0, 10),
            'max' => $this->faker->numberBetween(11, 20),
        ];
    }
}
