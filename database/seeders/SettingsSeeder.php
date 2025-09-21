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
        Setting::query()->firstOrCreate(
            ['name' => 'working_day_start'],
            ['value' => '07:00']
        );

        Setting::query()->firstOrCreate(
            ['name' => 'working_day_end'],
            ['value' => '20:00']
        );

        Setting::query()->firstOrCreate(
            ['name' => 'is_enabled_work_schedule'],
            ['value' => '1']
        );

        Setting::query()->firstOrCreate(
            ['name' => 'api_key_wb'],
            ['value' => '']
        );

        Setting::query()->firstOrCreate(
            ['name' => 'api_key_ozon'],
            ['value' => '']
        );

        Setting::query()->firstOrCreate(
            ['name' => 'seller_id_ozon'],
            ['value' => '']
        );

        Setting::query()->firstOrCreate(
            ['name' => 'max_quantity_orders_to_seamstress'],
            ['value' => '7']
        );

        Setting::query()->firstOrCreate(
            ['name' => 'orders_priority'],
            ['value' => 'by_date']
        );

        Setting::query()->firstOrCreate(
            ['name' => 'late_opened_shift_penalty'],
            ['value' => '0']
        );

        Setting::query()->firstOrCreate(
            ['name' => 'unclosed_shift_penalty'],
            ['value' => '0']
        );

        Setting::query()->firstOrCreate(
            ['name' => 'is_enabled_work_shift'],
            ['value' => '0']
        );
    }
}
