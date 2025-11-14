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
        $roleNames = ['admin', 'seamstress', 'storekeeper', 'cutter', 'otk'];
        $name = $this->faker->randomElement($roleNames);

        return [
            'name' => $name.'_'.$this->faker->unique()->randomNumber(5),
        ];
    }
}
