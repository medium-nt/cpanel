<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::query()->create([
            'name' => 'working_day_start',
            'value' => '7:00',
        ]);

        Setting::query()->create([
            'name' => 'working_day_end',
            'value' => '20:00',
        ]);

        Setting::query()->create([
            'name' => 'is_enabled_work_schedule',
            'value' => '1',
        ]);

        Setting::query()->create([
            'name' => 'api_key_wb',
            'value' => '',
        ]);

        Setting::query()->create([
            'name' => 'api_key_ozon',
            'value' => '',
        ]);

        Setting::query()->create([
            'name' => 'seller_id_ozon',
            'value' => '',
        ]);

    }
}
