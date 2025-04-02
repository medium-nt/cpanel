<?php

namespace Database\Seeders;

use App\Models\TypeMaterial;
use Illuminate\Database\Seeder;

class TypeMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeMaterial::query()->create(
            ['title' => 'Тюль']
        );

        TypeMaterial::query()->create(
            ['title' => 'Аксессуары']
        );

        TypeMaterial::query()->create(
            ['title' => 'Упаковка']
        );
    }
}
