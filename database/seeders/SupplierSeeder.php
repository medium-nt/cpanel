<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::query()->create(
            [
                'title' => 'ИП Иванов'
            ]
        );

        Supplier::query()->create(
            [
                'title' => 'ООО Ромашка'
            ]
        );

        Supplier::query()->create(
            [
                'title' => 'China LTD'
            ]
        );
    }
}
