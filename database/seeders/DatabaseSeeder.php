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

        // Базовые справочники
        $this->call(WorkshopSeeder::class);
        $this->call(ShiftSeeder::class);

        // Производство
        $this->call(RollSeeder::class);

        $this->call(OrderSeeder::class);
        $this->call(MovementMaterialSeeder::class);

        // Маркетплейсы
        $this->call(MarketplaceItemSeeder::class);
        $this->call(MarketplaceOrderSeeder::class);
        $this->call(MarketplaceOrderItemSeeder::class);
        $this->call(MarketplaceSupplySeeder::class);
        $this->call(SupplyBoxSeeder::class);
        $this->call(SkuSeeder::class);
        $this->call(ProductStickerSeeder::class);

        // Инвентаризация
        $this->call(InventoryCheckSeeder::class);
        $this->call(InventoryCheckItemSeeder::class);

        // Зарплата и тарифы
        $this->call(RateSeeder::class);
        $this->call(MotivationSeeder::class);
        $this->call(TariffSeeder::class);

        // Связующие таблицы
        $this->call(ItemWorkshopSeeder::class);
        $this->call(MaterialWorkshopSeeder::class);

        // Остальное
        $this->call(TransactionSeeder::class);
        $this->call(ScheduleSeeder::class);
        $this->call(SettingsSeeder::class);

    }
}
