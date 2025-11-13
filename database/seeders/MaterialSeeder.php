<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Material::query()->create(
            [
                'title' => 'Бамбук',
                'type_id' => 1,
                'height' => 200,
                'unit' => 'п.м.',
            ]
        );

        Material::query()->create(
            [
                'title' => 'Лен',
                'type_id' => 1,
                'height' => 225,
                'unit' => 'п.м.',
            ]
        );

        Material::query()->create(
            [
                'title' => 'Коробка 200х20х20',
                'type_id' => 3,
                'height' => 0,
                'unit' => 'шт.',
            ]
        );
    }
}
