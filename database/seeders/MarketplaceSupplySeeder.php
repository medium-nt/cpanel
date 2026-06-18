<?php

namespace Database\Seeders;

use App\Models\MarketplaceSupply;
use Illuminate\Database\Seeder;

class MarketplaceSupplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт поставки с учётом бизнес-правила: только 1 FBS на Ozon
     */
    public function run(): void
    {
        // Ozon FBS - только 1 поставка (бизнес-правило)
        MarketplaceSupply::query()->firstOrCreate(
            ['supply_id' => 'OZON-FBS-'.now()->format('Ymd')],
            [
                'marketplace_id' => 1,
                'type' => 'FBS',
                'cluster' => 'Центр',
                'supply_date' => now()->addDays(2)->toDateString(),
                'delivery_type' => MarketplaceSupply::DELIVERY_TYPE_BOX,
                'status' => 0,
            ]
        );

        // Ozon FBO - 3 поставки
        for ($i = 1; $i <= 3; $i++) {
            MarketplaceSupply::query()->firstOrCreate(
                ['supply_id' => 'OZON-FBO-'.now()->format('Ymd').'-'.$i],
                [
                    'marketplace_id' => 1,
                    'type' => 'FBO',
                    'cluster' => 'Центр',
                    'supply_date' => now()->addDays($i + 2)->toDateString(),
                    'delivery_type' => MarketplaceSupply::DELIVERY_TYPE_BOX,
                    'status' => $i === 1 ? 0 : 1, // Первая - открыта, остальные - в работе
                ]
            );
        }

        // WB FBS - 3 поставки
        for ($i = 1; $i <= 3; $i++) {
            MarketplaceSupply::query()->firstOrCreate(
                ['supply_id' => 'WB-FBS-'.now()->format('Ymd').'-'.$i],
                [
                    'marketplace_id' => 2,
                    'type' => 'FBS',
                    'cluster' => 'Север',
                    'supply_date' => now()->addDays($i + 1)->toDateString(),
                    'delivery_type' => MarketplaceSupply::DELIVERY_TYPE_BOX,
                    'status' => $i === 1 ? 0 : rand(0, 1),
                ]
            );
        }

        // WB FBO - 2 поставки
        for ($i = 1; $i <= 2; $i++) {
            MarketplaceSupply::query()->firstOrCreate(
                ['supply_id' => 'WB-FBO-'.now()->format('Ymd').'-'.$i],
                [
                    'marketplace_id' => 2,
                    'type' => 'FBO',
                    'cluster' => 'Север',
                    'supply_date' => now()->addDays($i + 3)->toDateString(),
                    'delivery_type' => MarketplaceSupply::DELIVERY_TYPE_BOX,
                    'status' => $i === 1 ? 0 : 1,
                ]
            );
        }
    }
}
