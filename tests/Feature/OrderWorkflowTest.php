<?php

namespace Tests\Feature;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Role;
use App\Models\Shelf;
use App\Models\User;
use App\Services\MarketplaceOrderService;
use App\Services\OrderService;
use App\Services\WarehouseOfItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $storekeeper;

    private User $seamstress;

    private User $cutter;

    private Shelf $shelf;

    private Material $material;

    private MarketplaceItem $marketplaceItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $storekeeperRole = Role::firstOrCreate(['name' => 'storekeeper']);
        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);
        $cutterRole = Role::firstOrCreate(['name' => 'cutter']);

        // Create users
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->storekeeper = User::factory()->create(['role_id' => $storekeeperRole->id]);
        $this->seamstress = User::factory()->create(['role_id' => $seamstressRole->id]);
        $this->cutter = User::factory()->create(['role_id' => $cutterRole->id]);

        // Set open shifts for testing
        $this->storekeeper->shift_is_open = true;
        $this->storekeeper->save();
        $this->seamstress->shift_is_open = true;
        $this->seamstress->save();
        $this->cutter->shift_is_open = true;
        $this->cutter->save();

        // Create test data
        $this->shelf = Shelf::create(['title' => 'Test Shelf']);
        $this->material = Material::factory()->create(['title' => 'Test Material', 'unit' => 'шт']);
        $this->marketplaceItem = MarketplaceItem::factory()->create();
    }

    #[Test]
    public function complete_marketplace_order_workflow_from_creation_to_completion()
    {
        // Step 1: Create marketplace order
        $order = MarketplaceOrder::factory()->create([
            'order_id' => 'TEST-12345',
            'marketplace_id' => 1,
            'status' => 0, // Новый
        ]);

        $orderItem = MarketplaceOrderItem::factory()->for($order)->create([
            'marketplace_item_id' => $this->marketplaceItem->id,
            'quantity' => 1,
            'status' => 0,
            'price' => 100,
            'seamstress_id' => $this->seamstress->id,
            'cutter_id' => $this->cutter->id,
        ]);

        // Verify initial state
        $this->assertEquals(0, $order->status);
        $this->assertEquals(0, $orderItem->status);
        $this->assertNull($orderItem->storage_barcode);
        $this->assertNull($orderItem->shelf_id);

        // Step 2: Move to production (status 13 - на сборке)
        $orderItem->update(['status' => 13]);
        $order->update(['status' => 13]);

        $this->assertEquals(13, $order->status);
        $this->assertEquals(13, $orderItem->status);

        // Step 3: Generate storage barcode
        $warehouseService = new WarehouseOfItemService;
        $barcode = $warehouseService->getStorageBarcode($orderItem);

        $this->assertNotNull($barcode);
        $orderItem->refresh();
        $this->assertEquals($barcode, $orderItem->storage_barcode);

        // Step 4: Move to storage (status 11 - на хранении)
        $warehouseService->saveItemToStorage($orderItem, $this->shelf->id);

        $orderItem->refresh();
        $order->refresh();
        $this->assertEquals(11, $orderItem->status);
        $this->assertEquals($this->shelf->id, $orderItem->shelf_id);
        $this->assertEquals(9, $order->status); // Order status changes to 9

        // Step 5: Prepare for shipping (status 5 - стикеровка)
        $order->update(['status' => 5]);
        $orderItem->update(['status' => 5]);

        $this->assertEquals(5, $order->status);
        $this->assertEquals(5, $orderItem->status);

        // Step 6: Complete order (status 1 - завершено)
        $order->update([
            'status' => 1,
            'completed_at' => now(),
        ]);
        $orderItem->update(['status' => 1]);

        $this->assertEquals(1, $order->status);
        $this->assertEquals(1, $orderItem->status);
        $this->assertNotNull($order->completed_at);
    }

    #[Test]
    public function material_movement_workflow_from_supplier_to_production()
    {
        // Step 1: Receive materials from supplier (type_movement = 1)
        $incomingOrder = Order::factory()->create([
            'type_movement' => 1,
            'status' => 3, // Завершено
            'storekeeper_id' => $this->storekeeper->id,
        ]);

        MovementMaterial::create([
            'order_id' => $incomingOrder->id,
            'material_id' => $this->material->id,
            'quantity' => 100,
        ]);

        // Verify material received in warehouse
        $this->actingAs($this->storekeeper);
        $warehouseQuantity = OrderService::getFiltered(new Request([
            'type_movement' => 1,
            'status' => 3,
        ]))->get()->sum(function ($order) {
            return $order->movementMaterials->sum('quantity');
        });

        $this->assertEquals(100, $warehouseQuantity);

        // Step 2: Move materials to workshop (type_movement = 2)
        $toWorkshopOrder = Order::factory()->create([
            'type_movement' => 2,
            'status' => 1, // Одобрено
            'storekeeper_id' => $this->storekeeper->id,
        ]);

        MovementMaterial::create([
            'order_id' => $toWorkshopOrder->id,
            'material_id' => $this->material->id,
            'quantity' => 50,
        ]);

        // Step 3: Complete material transfer
        $toWorkshopOrder->update(['status' => 3]);

        // Verify workshop has materials
        $workshopQuantity = OrderService::getFiltered(new Request([
            'type_movement' => 2,
            'status' => 3,
        ]))->get()->sum(function ($order) {
            return $order->movementMaterials->sum('quantity');
        });

        $this->assertEquals(50, $workshopQuantity);
    }

    #[Test]
    public function defect_material_workflow()
    {
        // Step 1: Materials in workshop get damaged (type_movement = 4)
        $defectOrder = Order::factory()->create([
            'type_movement' => 4,
            'status' => 1, // Одобрено
            'storekeeper_id' => $this->storekeeper->id,
        ]);

        MovementMaterial::create([
            'order_id' => $defectOrder->id,
            'material_id' => $this->material->id,
            'quantity' => 10,
        ]);

        // Step 2: Return defective materials to supplier (type_movement = 5)
        $returnOrder = Order::factory()->create([
            'type_movement' => 5,
            'status' => 1, // Одобрено
            'storekeeper_id' => $this->storekeeper->id,
        ]);

        MovementMaterial::create([
            'order_id' => $returnOrder->id,
            'material_id' => $this->material->id,
            'quantity' => 8,
        ]);

        // Complete return
        $returnOrder->update(['status' => 3]);

        // Verify return processed
        $returnedQuantity = OrderService::getFiltered(new Request([
            'type_movement' => 5,
            'status' => 3,
        ]))->get()->sum(function ($order) {
            return $order->movementMaterials->sum('quantity');
        });

        $this->assertEquals(8, $returnedQuantity);
    }

    #[Test]
    public function inventory_check_workflow()
    {
        // Create items in storage for inventory
        $order = MarketplaceOrder::factory()->create(['status' => 1]);
        $items = MarketplaceOrderItem::factory()->count(3)->for($order)->create([
            'status' => 11, // На хранении
            'shelf_id' => $this->shelf->id,
            'price' => 100,
            'seamstress_id' => $this->seamstress->id,
        ]);

        $this->actingAs($this->storekeeper);

        // Create inventory check
        $request = new Request([
            'comment' => 'Test Inventory Check',
            'inventory_shelf' => $this->shelf->id,
        ]);

        $inventoryService = app(\App\Services\InventoryService::class);
        $result = $inventoryService->createInventory($request);

        $this->assertTrue($result);

        // Verify inventory check created
        $this->assertDatabaseHas('inventory_checks', [
            'comment' => 'Test Inventory Check',
        ]);

        $this->assertDatabaseCount('inventory_check_items', 3);
    }

    #[Test]
    public function write_off_remnants_workflow()
    {
        $this->actingAs($this->storekeeper);

        // Create initial material receipt
        $incomingOrder = Order::factory()->create([
            'type_movement' => 1,
            'status' => 3,
            'storekeeper_id' => $this->storekeeper->id,
        ]);

        MovementMaterial::create([
            'order_id' => $incomingOrder->id,
            'material_id' => $this->material->id,
            'quantity' => 100,
        ]);

        // Write off remnants (type_movement = 8)
        $request = new \App\Http\Requests\StoreRemnantsRequest([
            'material_id' => [$this->material->id],
            'ordered_quantity' => [10],
            'comment' => 'Write off test',
        ]);

        $service = new \App\Services\WriteOffRemnantService;
        $response = $service->store($request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);

        // Verify write-off order created
        $this->assertDatabaseHas('orders', [
            'type_movement' => 8,
            'status' => 3,
            'comment' => 'Write off test',
            'storekeeper_id' => $this->storekeeper->id,
        ]);

        $this->assertDatabaseHas('movement_materials', [
            'material_id' => $this->material->id,
            'quantity' => 10,
        ]);
    }

    #[Test]
    public function order_status_transitions_are_valid()
    {
        $order = MarketplaceOrder::factory()->create(['status' => 0]);
        $item = MarketplaceOrderItem::factory()->for($order)->create(['status' => 0]);

        // Test valid status transitions
        $validTransitions = [
            0 => [13, 9], // Новый -> На сборке, Возврат
            13 => [5, 11], // На сборке -> Стикеровка, На хранении
            5 => [1], // Стикеровка -> Завершено
            11 => [5], // На хранении -> Стикеровка
        ];

        foreach ($validTransitions as $fromStatus => $toStatuses) {
            $order->update(['status' => $fromStatus]);
            $item->update(['status' => $fromStatus]);

            foreach ($toStatuses as $toStatus) {
                $order->update(['status' => $toStatus]);
                $item->update(['status' => $toStatus]);

                $this->assertEquals($toStatus, $order->status);
                $this->assertEquals($toStatus, $item->status);
            }
        }
    }

    #[Test]
    public function workflow_requires_proper_user_permissions()
    {
        $order = MarketplaceOrder::factory()->create(['status' => 13]);
        $item = MarketplaceOrderItem::factory()->for($order)->create(['status' => 13]);

        // Test that seamstress cannot access warehouse functions
        $this->actingAs($this->seamstress);
        $this->seamstress->shift_is_open = false;
        $this->seamstress->save();

        $this->get(route('materials.index'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('error', 'Откройте смену на терминале для доступа к функционалу.');

        // Test that storekeeper can access warehouse functions with open shift
        $this->actingAs($this->storekeeper);
        $this->get(route('materials.index'))->assertOk();
    }

    #[Test]
    public function workflow_generates_proper_logging()
    {
        $this->actingAs($this->admin);

        // Create order and verify logging
        $request = new \App\Http\Requests\StoreMarketplaceOrderRequest([
            'order_id' => 'LOG-TEST',
            'marketplace_id' => '1',
            'fulfillment_type' => 'FBO',
            'item_id' => [$this->marketplaceItem->id],
            'quantity' => [1],
        ]);

        \Log::shouldReceive('channel')->with('erp')->andReturnSelf();
        \Log::shouldReceive('notice')->once()->with(
            'Вручную добавлен новый заказ: LOG-TEST-1 (OZON)'
        );

        MarketplaceOrderService::store($request);
    }
}
