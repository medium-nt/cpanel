<?php

namespace Tests\Feature\Services;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Role;
use App\Models\Shelf;
use App\Models\User;
use App\Services\WarehouseOfItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class WarehouseOfItemServiceTest extends TestCase
{
    use RefreshDatabase;

    private WarehouseOfItemService $warehouseOfItemService;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(
            ['name' => 'seamstress']
        );

        $this->warehouseOfItemService = $this->app->make(WarehouseOfItemService::class);
    }

    public function test_get_storage_barcode_generates_new_one()
    {
        $order = MarketplaceOrder::factory()->create(['status' => 1]);
        $seamstress = User::factory()->create();
        $marketplaceOrderItem = MarketplaceOrderItem::factory()
            ->for($order)
            ->create([
                'storage_barcode' => null,
                'price' => 100,
                'seamstress_id' => $seamstress->id,
            ]);

        $this->assertNull($marketplaceOrderItem->storage_barcode);

        $barcode = $this->warehouseOfItemService->getStorageBarcode($marketplaceOrderItem);

        $this->assertNotNull($barcode);

        $this->assertDatabaseHas('marketplace_order_items', [
            'id' => $marketplaceOrderItem->id,
            'storage_barcode' => $barcode,
        ]);
    }

    public function test_get_storage_barcode_returns_existing_one()
    {
        $existingBarcode = 'EXISTING-123';
        $order = MarketplaceOrder::factory()->create(['status' => 1]);
        $seamstress = User::factory()->create();
        $marketplaceOrderItem = MarketplaceOrderItem::factory()
            ->for($order)
            ->create([
                'storage_barcode' => $existingBarcode,
                'price' => 100,
                'seamstress_id' => $seamstress->id,
            ]);

        $barcode = $this->warehouseOfItemService->getStorageBarcode($marketplaceOrderItem);
        $this->assertEquals($existingBarcode, $barcode);
    }

    public function test_save_item_to_storage()
    {
        $order = MarketplaceOrder::factory()->create(['status' => 1]);
        $shelf = Shelf::create(['title' => 'Test Shelf']);
        $seamstress = User::factory()->create();
        $marketplaceOrderItem = MarketplaceOrderItem::factory()->for($order)->create([
            'status' => 10,
            'price' => 100,
            'seamstress_id' => $seamstress->id,
        ]);

        $this->warehouseOfItemService->saveItemToStorage($marketplaceOrderItem, $shelf->id);

        $this->assertDatabaseHas('marketplace_order_items', [
            'id' => $marketplaceOrderItem->id,
            'status' => 11,
            'shelf_id' => $shelf->id,
        ]);

        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'status' => 9,
        ]);
    }

    public function test_find_refund_item_by_barcode_not_found()
    {
        $result = $this->warehouseOfItemService->findRefundItemByBarcode('non-existent-barcode');
        $this->assertEquals('Нет такого заказа', $result['message']);
        $this->assertNull($result['marketplace_item']);
    }

    public function test_find_refund_item_by_barcode_already_in_storage()
    {
        $barcode = 'BARCODE-12345';
        $order = MarketplaceOrder::factory()->create(['status' => 1]);
        $item = MarketplaceItem::factory()->create();
        $seamstress = User::factory()->create();

        MarketplaceOrderItem::factory()
            ->for($order)
            ->create([
                'marketplace_item_id' => $item->id,
                'seamstress_id' => $seamstress->id,
                'storage_barcode' => $barcode,
                'status' => 11,
                'price' => 100,
            ]);

        $result = $this->warehouseOfItemService->findRefundItemByBarcode($barcode);
        $this->assertEquals('Товар уже находится на складе', $result['message']);
    }

    public function test_generated_barcode_has_valid_luhn_checksum(): void
    {
        $order = MarketplaceOrder::factory()->create(['status' => 1]);
        $seamstress = User::factory()->create();
        $marketplaceOrderItem = MarketplaceOrderItem::factory()
            ->for($order)
            ->create([
                'storage_barcode' => null,
                'price' => 100,
                'seamstress_id' => $seamstress->id,
            ]);

        $barcode = $this->warehouseOfItemService->getStorageBarcode($marketplaceOrderItem);

        // 8 базовых + 1 контрольная
        $this->assertSame(9, strlen($barcode));

        $base = substr($barcode, 0, 8);
        $check = (int)substr($barcode, -1);

        $sum = 0;
        foreach (str_split(strrev($base)) as $i => $d) {
            $n = (int)$d * ($i % 2 === 0 ? 2 : 1);
            $sum += $n > 9 ? $n - 9 : $n;
        }
        $this->assertSame($check, (10 - $sum % 10) % 10);
    }

    public function test_find_refund_item_by_barcode_success()
    {
        $orderId = '12345';
        $order = MarketplaceOrder::factory()->create([
            'status' => 1,
            'marketplace_id' => 2,
            'order_id' => $orderId,
        ]);
        $item = MarketplaceItem::factory()->create();
        $seamstress = User::factory()->create();

        $orderItem = MarketplaceOrderItem::factory()
            ->for($order)
            ->create([
                'marketplace_item_id' => $item->id,
                'seamstress_id' => $seamstress->id,
                'storage_barcode' => null,
                'status' => 3,
                'price' => 100,
            ]);

        $result = $this->warehouseOfItemService->findRefundItemByBarcode($orderId);
        $this->assertEquals($orderItem->id, $result['marketplace_item']->id);
    }

    public function test_get_create_items()
    {
        $shelf = Shelf::create(['title' => 'Test Shelf']);

        User::factory()->create();
        $item = MarketplaceItem::factory()->create();

        $validatedData = ['quantity' => 3, 'shelf_id' => $shelf->id];

        $createdItems = $this->warehouseOfItemService->getCreateItems($validatedData, $item);

        $this->assertCount(3, $createdItems);
        $this->assertDatabaseCount('marketplace_order_items', 3);

        $createdOrderItem = MarketplaceOrderItem::find($createdItems[0]);
        $this->assertEquals(11, $createdOrderItem->status);
        $this->assertEquals($shelf->id, $createdOrderItem->shelf_id);
        $this->assertNotNull($createdOrderItem->storage_barcode);
    }

    public function test_get_filtered()
    {
        $order = MarketplaceOrder::factory()->create(['status' => 1]);
        $item1 = MarketplaceItem::factory()->create();
        $item2 = MarketplaceItem::factory()->create();

        Role::query()->firstOrCreate(
            ['name' => 'seamstress']
        );

        $seamstress = User::factory()->create(['role_id' => 1]);

        MarketplaceOrderItem::factory()->count(5)->for($order)->create([
            'marketplace_item_id' => $item1->id,
            'status' => 9,
            'price' => 100,
            'seamstress_id' => $seamstress->id,
        ]);
        MarketplaceOrderItem::factory()->count(3)->for($order)->create([
            'marketplace_item_id' => $item2->id,
            'status' => 10,
            'price' => 100,
            'seamstress_id' => $seamstress->id,
        ]);

        $request = new Request(['status' => 9]);
        $filtered = $this->warehouseOfItemService->getFiltered($request);
        $this->assertEquals(5, $filtered->count());

        $request = new Request(['status' => 10]);
        $filtered = $this->warehouseOfItemService->getFiltered($request);
        $this->assertEquals(3, $filtered->count());

        $requestWithoutStatus = new Request;
        $filteredWithoutStatus = $this->warehouseOfItemService->getFiltered($requestWithoutStatus);
        $this->assertEquals(8, $filteredWithoutStatus->count());
    }
}
