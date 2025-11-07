<?php

namespace Database\Seeders;

use App\Models\Shelf;
use Illuminate\Database\Seeder;

class ShelfSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Shelf::query()->firstOrCreate(
            ['title' => 'первая']
        );

        Shelf::query()->firstOrCreate(
            ['title' => 'вторая']
        );

        Shelf::query()->firstOrCreate(
            ['title' => 'третья']
        );

    }
}
