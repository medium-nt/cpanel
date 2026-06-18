<?php

namespace Database\Seeders;

use App\Models\MarketplaceOrder;
use Illuminate\Database\Seeder;

class MarketplaceOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт 20 заказов для тестирования разных сценариев
     */
    public function run(): void
    {
        // Ozon FBO (5 заказов)
        $this->createOrder('OZON-FBO-001', 1, 'FBO', 0);
        $this->createOrder('OZON-FBO-002', 1, 'FBO', 1);
        $this->createOrder('OZON-FBO-003', 1, 'FBO', 3);
        $this->createOrder('OZON-FBO-004', 1, 'FBO', 4);
        $this->createOrder('OZON-FBO-005', 1, 'FBO', 9);

        // Ozon FBS (5 заказов)
        $this->createOrder('OZON-FBS-001', 1, 'FBS', 2);
        $this->createOrder('OZON-FBS-002', 1, 'FBS', 6);
        $this->createOrder('OZON-FBS-003', 1, 'FBS', 10);
        $this->createOrder('OZON-FBS-004', 1, 'FBS', 12);
        $this->createOrder('OZON-FBS-005', 1, 'FBS', 17);

        // WB FBO (5 заказов)
        $this->createOrder('WB-FBO-001', 2, 'FBO', 5);
        $this->createOrder('WB-FBO-002', 2, 'FBO', 7);
        $this->createOrder('WB-FBO-003', 2, 'FBO', 8);
        $this->createOrder('WB-FBO-004', 2, 'FBO', 11);
        $this->createOrder('WB-FBO-005', 2, 'FBO', 14);

        // WB FBS (5 заказов)
        $this->createOrder('WB-FBS-001', 2, 'FBS', 13);
        $this->createOrder('WB-FBS-002', 2, 'FBS', 15);
        $this->createOrder('WB-FBS-003', 2, 'FBS', 16);
        $this->createOrder('WB-FBS-004', 2, 'FBS', 18);
        $this->createOrder('WB-FBS-005', 2, 'FBS', 19);
    }

    /**
     * Создаёт заказ маркетплейса
     */
    private function createOrder(string $orderId, int $marketplaceId, string $fulfillmentType, int $status): void
    {
        MarketplaceOrder::query()->firstOrCreate(
            ['order_id' => $orderId],
            [
                'marketplace_id' => $marketplaceId,
                'fulfillment_type' => $fulfillmentType,
                'status' => (string) $status,
            ]
        );
    }
}
