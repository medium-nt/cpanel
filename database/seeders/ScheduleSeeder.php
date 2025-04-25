<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schedule::query()->create(
            [
                'user_id' => 3,
                'date' => now()->subDays(1),
            ]
        );

        Schedule::query()->create(
            [
                'user_id' => 3,
                'date' => now()->addDays(2),
            ]
        );

        Schedule::query()->create(
            [
                'user_id' => 3,
                'date' => now()->subDays(2),
            ]
        );

        Schedule::query()->create(
            [
                'user_id' => 3,
                'date' => now()->addDays(3),
            ]
        );

        Schedule::query()->create(
            [
                'user_id' => 3,
                'date' => now()->addDays(4),
            ]
        );
    }
}
