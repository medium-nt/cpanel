<?php

namespace Tests\Feature\Services;

use App\Http\Requests\StoreMarketplaceOrderRequest;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Services\MarketplaceOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Очищаем маркетплейс-данные — тесты проверяют точные счётчики заказов/позиций. */
    protected array $cleanTables = ['marketplace_orders', 'marketplace_order_items'];

    private function createValidStoreRequest(array $overrides = []): StoreMarketplaceOrderRequest
    {
        $request = new StoreMarketplaceOrderRequest;
        $request->initialize(array_merge([
            'order_id' => '1234567890',
            'marketplace_id' => '1',
            'fulfillment_type' => 'FBO',
            'cluster' => 'test-cluster',
            'item_id' => [1, 2],
            'quantity' => [2, 3],
        ], $overrides));

        return $request;
    }

    public function test_store_with_fbo_fulfillment_type(): void
    {
        // Arrange
        MarketplaceItem::factory()->create();
        MarketplaceItem::factory()->create();
        $request = $this->createValidStoreRequest();

        // Act
        $result = MarketplaceOrderService::store($request);

        // Assert
        $this->assertTrue($result);

        // Should create 5 orders (2 + 3)
        $this->assertDatabaseCount('marketplace_orders', 5);
        $this->assertDatabaseCount('marketplace_order_items', 5);

        // Check that orders have the correct structure
        $orders = MarketplaceOrder::where('order_id', 'like', '1234567890-%')->get();
        foreach ($orders as $order) {
            $this->assertStringStartsWith('1234567890-', $order->order_id);
            $this->assertEquals('1', $order->marketplace_id);
            $this->assertEquals('FBO', $order->fulfillment_type);
            $this->assertEquals(0, $order->status);
        }
    }

    public function test_store_with_fbs_fulfillment_type(): void
    {
        // Arrange
        $marketplaceItem = MarketplaceItem::factory()->create();
        $request = $this->createValidStoreRequest([
            'fulfillment_type' => 'FBS',
            'item_id' => [$marketplaceItem->id],
            'quantity' => [3],
        ]);

        // Act
        $result = MarketplaceOrderService::store($request);

        // Assert
        $this->assertTrue($result);

        // Should create 1 order (FBS creates single order with multiple items)
        $this->assertDatabaseCount('marketplace_orders', 1);
        $this->assertDatabaseCount('marketplace_order_items', 1);

        $order = MarketplaceOrder::first();
        $this->assertEquals('1234567890', $order->order_id);
        $this->assertEquals('FBS', $order->fulfillment_type);
    }

    public function test_store_with_empty_items(): void
    {
        // Arrange
        $request = $this->createValidStoreRequest([
            'item_id' => [],
            'quantity' => [],
        ]);

        // Act
        $result = MarketplaceOrderService::store($request);

        // Assert
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $result);
    }

    public function test_store_with_partial_empty_items(): void
    {
        // Arrange
        $marketplaceItem = MarketplaceItem::factory()->create();
        $request = $this->createValidStoreRequest([
            'item_id' => [$marketplaceItem->id, ''],
            'quantity' => [2, ''],
        ]);

        // Act
        $result = MarketplaceOrderService::store($request);

        // Assert
        $this->assertTrue($result);

        // Should create 2 orders (only the valid item)
        $this->assertDatabaseCount('marketplace_orders', 2);
        $this->assertDatabaseCount('marketplace_order_items', 2);
    }

    public function test_get_marketplace_name_for_ozon(): void
    {
        // Act
        $name = MarketplaceOrderService::getMarketplaceName('1');

        // Assert
        $this->assertEquals('OZON', $name);
    }

    public function test_get_marketplace_name_for_wb(): void
    {
        // Act
        $name = MarketplaceOrderService::getMarketplaceName('2');

        // Assert
        $this->assertEquals('WB', $name);
    }

    public function test_get_marketplace_name_for_unknown(): void
    {
        // Act
        $name = MarketplaceOrderService::getMarketplaceName('999');

        // Assert
        $this->assertEquals('---', $name);
    }

    public function test_pickup_orders(): void
    {
        // Arrange
        MarketplaceOrder::factory()->count(3)->create(['status' => 13]);
        MarketplaceOrder::factory()->count(2)->create(['status' => 5]);

        // Act
        $pickupOrders = MarketplaceOrderService::pickupOrders();

        // Assert
        $this->assertEquals(3, $pickupOrders->count());
        $pickupOrders->each(function ($order) {
            $this->assertEquals(13, $order->status);
        });
    }

    public function test_group_pickup_orders(): void
    {
        // Arrange
        $orders = MarketplaceOrder::factory()->count(2)->create(['status' => 13]);
        $shelf = \App\Models\Shelf::create(['title' => 'test-shelf']);

        // Create items for each order with marketplace items
        foreach ($orders as $order) {
            $marketplaceItem = MarketplaceItem::factory()->create();
            MarketplaceOrderItem::factory()->create([
                'marketplace_order_id' => $order->id,
                'status' => 13,
                'shelf_id' => $shelf->id,
                'marketplace_item_id' => $marketplaceItem->id,
            ]);
        }

        // Act
        $grouped = MarketplaceOrderService::groupPickupOrders($orders);

        // Assert
        $this->assertCount(2, $grouped);
        $this->assertArrayHasKey($orders[0]->id, $grouped);
        $this->assertArrayHasKey($orders[1]->id, $grouped);

        // Check that each group has shelf stats
        foreach ($grouped as $group) {
            $this->assertArrayHasKey('itemName', $group);
            $this->assertArrayHasKey('shelfStats', $group);
            $this->assertNotEmpty($group['shelfStats']);
        }
    }

    public function test_assembled_orders(): void
    {
        // Arrange
        // Create order with status 5 and item with status 13 (assembled)
        $order1 = MarketplaceOrder::factory()->create(['status' => 5]);
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $order1->id,
            'status' => 13,
        ]);

        // Create order with status 5 but item with different status
        $order2 = MarketplaceOrder::factory()->create(['status' => 5]);
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $order2->id,
            'status' => 11,
        ]);

        // Create order with different status
        $order3 = MarketplaceOrder::factory()->create(['status' => 3]);
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $order3->id,
            'status' => 13,
        ]);

        // Act
        $assembledOrders = MarketplaceOrderService::assembledOrders();

        // Assert
        $this->assertCount(1, $assembledOrders);
        $this->assertEquals($order1->id, $assembledOrders->first()->id);
    }

    public function test_has_shipped_orders_by_supply_with_wb(): void
    {
        // Arrange
        $supply = MarketplaceSupply::factory()->create(['marketplace_id' => 2, 'status' => 1]);

        // Create order with different status than expected for WB (confirm)
        MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'marketplace_status' => 'shipped',
        ]);

        // Act
        $result = MarketplaceOrderService::hasShippedOrdersBySupply($supply);

        // Assert
        $this->assertTrue($result);
    }

    public function test_has_shipped_orders_by_supply_with_ozon(): void
    {
        // Arrange
        $supply = MarketplaceSupply::factory()->create(['marketplace_id' => 1, 'status' => 1]);

        // Create order with different status than expected for OZON (awaiting_deliver)
        MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'marketplace_status' => 'shipped',
        ]);

        // Act
        $result = MarketplaceOrderService::hasShippedOrdersBySupply($supply);

        // Assert
        $this->assertTrue($result);
    }

    public function test_has_shipped_orders_by_supply_with_no_shipped_orders(): void
    {
        // Arrange
        $supply = MarketplaceSupply::factory()->create(['marketplace_id' => 1, 'status' => 1]);

        // Create order with correct status for OZON
        MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'marketplace_status' => 'awaiting_deliver',
        ]);

        // Act
        $result = MarketplaceOrderService::hasShippedOrdersBySupply($supply);

        // Assert
        $this->assertFalse($result);
    }

    public function test_has_shipped_orders_by_supply_with_null_status(): void
    {
        // Arrange
        $supply = MarketplaceSupply::factory()->create(['marketplace_id' => 1, 'status' => 1]);

        // Create order with null marketplace status
        MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'marketplace_status' => null,
        ]);

        // Act
        $result = MarketplaceOrderService::hasShippedOrdersBySupply($supply);

        // Assert
        $this->assertTrue($result);
    }

    public function test_delete_order_with_all_items_status_0(): void
    {
        // Arrange
        $order = MarketplaceOrder::factory()->create();
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $order->id,
            'status' => 0,
        ]);

        // Act
        $result = MarketplaceOrderService::delete($order);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('marketplace_orders', ['id' => $order->id]);
    }

    public function test_delete_order_with_mixed_status_items(): void
    {
        // Arrange
        $order = MarketplaceOrder::factory()->create();
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $order->id,
            'status' => 0,
        ]);
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $order->id,
            'status' => 5,
        ]);

        // Act
        $result = MarketplaceOrderService::delete($order);

        // Assert
        $this->assertFalse($result);
        $this->assertDatabaseHas('marketplace_orders', ['id' => $order->id]);
    }

    public function test_delete_new_orders_by_supply(): void
    {
        // Arrange
        $supply = MarketplaceSupply::factory()->create();

        // Create 3 new orders for this supply
        $orders = [];
        for ($i = 0; $i < 3; $i++) {
            $orders[] = MarketplaceOrder::factory()->create([
                'supply_id' => $supply->id,
                'status' => 0,
            ]);
        }

        // Create 1 order with different status
        MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'status' => 5,
        ]);

        // Count initial orders
        $initialCount = MarketplaceOrder::count();

        // Act
        $result = MarketplaceOrderService::deleteNewOrdersBySupply($supply->id);

        // Assert
        $this->assertEquals(['deleted' => 3, 'skipped' => 0], $result);
        $this->assertDatabaseCount('marketplace_orders', $initialCount - 3); // 3 deleted, 1 old order remains

        // Check that deleted orders are actually removed
        foreach ($orders as $order) {
            $this->assertDatabaseMissing('marketplace_orders', ['id' => $order->id]);
        }
    }

    public function test_delete_new_orders_by_supply_with_no_new_orders(): void
    {
        // Arrange
        $supply = MarketplaceSupply::factory()->create();

        // Create only non-new orders
        MarketplaceOrder::factory()->count(2)->create([
            'supply_id' => $supply->id,
            'status' => 5,
        ]);

        // Act
        $result = MarketplaceOrderService::deleteNewOrdersBySupply($supply->id);

        // Assert
        $this->assertEquals(['deleted' => 0, 'skipped' => 0], $result);
        $this->assertDatabaseCount('marketplace_orders', 2); // No orders deleted
    }

    public function test_detach_not_ready_orders_by_supply(): void
    {
        // Arrange
        $supply = MarketplaceSupply::factory()->create();

        // Create supply boxes for ready orders
        $box1 = \App\Models\SupplyBox::create([
            'marketplace_supply_id' => $supply->id,
            'number' => 'BOX-001',
        ]);
        $box2 = \App\Models\SupplyBox::create([
            'marketplace_supply_id' => $supply->id,
            'number' => 'BOX-002',
        ]);

        // Create ready orders (status 4 with box_id not null) - should not be detached
        $readyOrder1 = MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'status' => 4,
            'box_id' => $box1->id,
        ]);

        $readyOrder2 = MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'status' => 4,
            'box_id' => $box2->id,
        ]);

        // Create not ready orders (status 4 with box_id null) - should be detached
        $notReadyOrder1 = MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'status' => 4,
            'box_id' => null,
        ]);

        $notReadyOrder2 = MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'status' => 4,
            'box_id' => null,
        ]);

        // Create order with different status - should not be detached
        $differentStatusOrder = MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'status' => 5,
            'box_id' => null,
        ]);

        // Act
        $result = MarketplaceOrderService::detachNotReadyOrdersBySupply($supply->id);

        // Assert
        $this->assertEquals(['detached' => 2], $result);

        // Check that ready orders still have supply_id
        $this->assertNotNull($readyOrder1->fresh()->supply_id);
        $this->assertNotNull($readyOrder2->fresh()->supply_id);

        // Check that not ready orders have null supply_id
        $this->assertNull($notReadyOrder1->fresh()->supply_id);
        $this->assertNull($notReadyOrder2->fresh()->supply_id);

        // Check that different status order still has supply_id
        $this->assertNotNull($differentStatusOrder->fresh()->supply_id);
    }

    public function test_detach_not_ready_orders_by_supply_with_no_orders_to_detach(): void
    {
        // Arrange
        $supply = MarketplaceSupply::factory()->create();

        // Create supply boxes for ready orders
        $box1 = \App\Models\SupplyBox::create([
            'marketplace_supply_id' => $supply->id,
            'number' => 'BOX-001',
        ]);
        $box2 = \App\Models\SupplyBox::create([
            'marketplace_supply_id' => $supply->id,
            'number' => 'BOX-002',
        ]);

        // Create only ready orders (all have box_id not null)
        $readyOrder1 = MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'status' => 4,
            'box_id' => $box1->id,
        ]);

        $readyOrder2 = MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'status' => 4,
            'box_id' => $box2->id,
        ]);

        // Create orders with different status
        $differentStatusOrder = MarketplaceOrder::factory()->create([
            'supply_id' => $supply->id,
            'status' => 5,
            'box_id' => $box1->id,
        ]);

        // Act
        $result = MarketplaceOrderService::detachNotReadyOrdersBySupply($supply->id);

        // Assert
        $this->assertEquals(['detached' => 0], $result);
    }
}
