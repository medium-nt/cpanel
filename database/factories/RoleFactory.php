<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roleNames = ['admin', 'seamstress', 'storekeeper', 'cutter', 'otk', 'cleaner'];
        $name = $this->faker->randomElement($roleNames);

        return [
            'name' => $name.'_'.$this->faker->unique()->randomNumber(5),
        ];
    }

    /**
     * Create a specific role.
     */
    public function asAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
        ]);
    }

    /**
     * Create a shift worker role.
     */
    public function asShiftWorker(): static
    {
        $shiftRoles = ['seamstress', 'cutter', 'otk'];
        $role = $this->faker->randomElement($shiftRoles);

        return $this->state(fn (array $attributes) => [
            'name' => $role,
        ]);
    }

    /**
     * Create a non-shift worker role.
     */
    public function asNonShiftWorker(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'storekeeper',
        ]);
    }
}
