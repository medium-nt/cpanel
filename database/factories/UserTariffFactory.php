<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserTariff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserTariff>
 */
class UserTariffFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserTariff::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['sewing', 'cutting', 'repacking', 'sticking', 'salary_daily']),
            'type' => $this->faker->randomElement(['per_meter', 'per_piece']),
            'is_bonus' => false,
        ];
    }

    /**
     * Create a bonus tariff
     */
    public function bonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_bonus' => true,
        ]);
    }

    /**
     * Create a sewing action tariff
     */
    public function sewing(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'sewing',
        ]);
    }

    /**
     * Create a cutting action tariff
     */
    public function cutting(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'cutting',
        ]);
    }

    /**
     * Create a repacking action tariff
     */
    public function repacking(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'repacking',
        ]);
    }

    /**
     * Create a sticking action tariff
     */
    public function sticking(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'sticking',
        ]);
    }

    /**
     * Create a salary daily action tariff
     */
    public function salaryDaily(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'salary_daily',
        ]);
    }

    /**
     * Create a per meter type tariff
     */
    public function perMeter(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'per_meter',
        ]);
    }

    /**
     * Create a per piece type tariff
     */
    public function perPiece(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'per_piece',
        ]);
    }
}
