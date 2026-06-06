<?php

namespace Database\Factories;

use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workshop>
 */
class WorkshopFactory extends Factory
{
    protected $model = Workshop::class;

    /**
     * Фабрика для создания тестового цеха.
     */
    public function definition(): array
    {
        return [
            'title' => 'Цех №'.$this->faker->unique()->numberBetween(1, 99),
            'status' => Workshop::STATUS_ACTIVE,
        ];
    }
}
