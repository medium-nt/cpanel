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
        Role::query()->create(
            ['name' => 'seamstress']
        );

        Role::query()->create(
            ['name' => 'storekeeper']
        );

        Role::query()->create(
            ['name' => 'admin']
        );
    }
}
