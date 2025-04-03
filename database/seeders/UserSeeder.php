<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(10)->create();

        User::query()->create([
            'name' => 'Admin',
            'email' => '1@1.ru',
            'password' => bcrypt('111111'),
            'role_id' => 3
        ]);
    }
}
