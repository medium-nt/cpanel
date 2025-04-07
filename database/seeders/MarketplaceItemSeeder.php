<?php

namespace Database\Seeders;

use App\Models\MarketplaceItem;
use Illuminate\Database\Seeder;

class MarketplaceItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MarketplaceItem::query()->create(
            [
                'sku' => '123456789',
                'title' => 'Тюль Бамбук',
                'width' => 200,
                'height' => 220,
                'marketplace_id' => 1
            ]
        );

        MarketplaceItem::query()->create(
            [
                'sku' => '987654321',
                'title' => 'Тюль Лен',
                'width' => 300,
                'height' => 240,
                'marketplace_id' => 1
            ]
        );

        MarketplaceItem::query()->create(
            [
                'sku' => '192837465',
                'title' => 'Тюль Вуаль',
                'width' => 400,
                'height' => 225,
                'marketplace_id' => 2
            ]
        );
    }
}
