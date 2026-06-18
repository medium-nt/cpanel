<?php

namespace Database\Seeders;

use App\Models\MarketplaceOrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketplaceOrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Создаёт 20 items с разными статусами для тестирования
     * По 1 item на каждый тестовый заказ
     */
    public function run(): void
    {
        // Получаем ID созданных заказов
        $orders = DB::table('marketplace_orders')
            ->where('order_id', 'like', 'OZON-%')
            ->orWhere('order_id', 'like', 'WB-%')
            ->orderBy('id')
            ->pluck('id');

        // Получаем ID товаров (берём первые 20)
        $items = DB::table('marketplace_items')
            ->orderBy('id')
            ->limit(20)
            ->pluck('id');

        if ($orders->count() < 20 || $items->count() < 20) {
            $this->command->warn('Недостаточно данных для создания items. Сначала запустите MarketplaceOrderSeeder и MarketplaceItemSeeder.');

            return;
        }

        // Ozon FBO (5): statuses 0, 1, 3, 4, 9
        $this->createItem($orders[0], $items[0], 0, 899.99);
        $this->createItem($orders[1], $items[1], 1, 1299.00);
        $this->createItem($orders[2], $items[2], 3, 750.50);
        $this->createItem($orders[3], $items[3], 4, 1599.99);
        $this->createItem($orders[4], $items[4], 9, 450.00);

        // Ozon FBS (5): statuses 2, 6, 10, 12, 17
        $this->createItem($orders[5], $items[5], 2, 999.00);
        $this->createItem($orders[6], $items[6], 6, 1100.00);
        $this->createItem($orders[7], $items[7], 10, 675.50);
        $this->createItem($orders[8], $items[8], 12, 899.99);
        $this->createItem($orders[9], $items[9], 17, 0);

        // WB FBO (5): statuses 5, 7, 8, 11, 14
        $this->createItem($orders[10], $items[10], 5, 1350.00);
        $this->createItem($orders[11], $items[11], 7, 1450.00);
        $this->createItem($orders[12], $items[12], 8, 1150.50);
        $this->createItem($orders[13], $items[13], 11, 999.99);
        $this->createItem($orders[14], $items[14], 14, 0);

        // WB FBS (5): statuses 13, 15, 16, 18, 19
        $this->createItem($orders[15], $items[15], 13, 1250.00);
        $this->createItem($orders[16], $items[16], 15, 875.50);
        $this->createItem($orders[17], $items[17], 16, 550.00);
        $this->createItem($orders[18], $items[18], 18, 1100.00);
        $this->createItem($orders[19], $items[19], 19, 0);
    }

    /**
     * Создаёт элемент заказа маркетплейса
     */
    private function createItem(int $orderOrderId, int $itemId, int $status, float $price): void
    {
        MarketplaceOrderItem::query()->create([
            'marketplace_order_id' => $orderOrderId,
            'marketplace_item_id' => $itemId,
            'quantity' => 1,
            'price' => $price,
            'status' => $status,
        ]);
    }
}
