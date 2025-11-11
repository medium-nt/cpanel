<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::query()->firstOrCreate(
            ['name' => 'seamstress']
        );

        Role::query()->firstOrCreate(
            ['name' => 'storekeeper']
        );

        Role::query()->firstOrCreate(
            ['name' => 'admin']
        );

        Role::query()->firstOrCreate(
            ['name' => 'cutter']
        );

        Role::query()->firstOrCreate(
            ['name' => 'otk']
        );
    }
}
