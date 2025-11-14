<?php

namespace Database\Factories;

use App\Models\TypeMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TypeMaterialFactory extends Factory
{
    protected $model = TypeMaterial::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
