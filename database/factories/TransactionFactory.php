<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence,
            'accrual_for_date' => $this->faker->date(),
            'amount' => $this->faker->numberBetween(100, 1000),
            'transaction_type' => $this->faker->randomElement(['in', 'out']),
            'status' => $this->faker->randomElement([0, 1, 2]),
            'is_bonus' => $this->faker->boolean,
            'paid_at' => null,
        ];
    }
}
