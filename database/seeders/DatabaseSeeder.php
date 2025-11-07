<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         $this->call(RoleSeeder::class);
         $this->call(UserSeeder::class);

         $this->call(TypeMaterialSeeder::class);
         $this->call(MaterialSeeder::class);

         $this->call(SupplierSeeder::class);

        $this->call(ShelfSeeder::class);

         $this->call(OrderSeeder::class);
         $this->call(MovementMaterialSeeder::class);

         $this->call(MarketplaceItemSeeder::class);

         $this->call(TransactionSeeder::class);

         $this->call(ScheduleSeeder::class);

         $this->call(SettingsSeeder::class);

    }
}
