<?php

namespace Tests\Feature\Services;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Shelf;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = $this->app->make(InventoryService::class);
    }

    private function createMovement(Material $material, int $type, int $status, float $quantity): void
    {
        $order = Order::factory()->create([
            'type_movement' => $type,
            'status' => $status,
        ]);

        MovementMaterial::create([
            'material_id' => $material->id,
            'order_id' => $order->id,
            'quantity' => $quantity,
        ]);
    }

    public function test_count_material()
    {
        $material = Material::factory()->create(['title' => 'Test Material', 'unit' => 'шт']);
        $this->createMovement($material, 1, 3, 100.5);
        $this->createMovement($material, 1, 3, 50.0);
        $this->createMovement($material, 2, 1, 25.0);

        $result = InventoryService::countMaterial($material->id, 1, 3);

        $this->assertEquals(150.5, $result);
    }

    public function test_material_in_warehouse()
    {
        $material = Material::factory()
            ->create([
                'title' => 'Test Material',
                'unit' => 'шт'
            ]);

        $this->createMovement($material, 1, 3, 100);
        $this->createMovement($material, 2, 0, 10);

        $order = Order::factory()->create(['type_movement' => 2, 'status' => 1]);
        MovementMaterial::create([
            'material_id' => $material->id,
            'order_id' => $order->id,
            'quantity' => 20
        ]);

        $result = InventoryService::materialInWarehouse($material->id);
        $this->assertEquals(70, $result);
    }

    public function test_material_in_workshop()
    {
        $material = Material::factory()->create(['title' => 'Test Material', 'unit' => 'шт']);

        $this->createMovement($material, 2, 3, 100);
        $this->createMovement($material, 3, 4, 10);
        $this->createMovement($material, 3, 3, 20);
        $this->createMovement($material, 4, 1, 5);
        $this->createMovement($material, 7, 1, 2);
        $this->createMovement($material, 6, 3, 3);

        $result = InventoryService::materialInWorkshop($material->id);

        $this->assertEquals(60, $result);
    }

    public function test_create_inventory_for_specific_shelf()
    {
        $shelf1 = Shelf::create(['title' => 'Shelf 1']);
        $shelf2 = Shelf::create(['title' => 'Shelf 2']);

        $order = MarketplaceOrder::factory()->create(['status' => 1]);
        $seamstress = User::factory()->create();

        $commonData = ['price' => 100, 'seamstress_id' => $seamstress->id];

        MarketplaceOrderItem::factory()->count(2)->for($order)->create(array_merge($commonData, ['status' => 11, 'shelf_id' => $shelf1->id]));
        MarketplaceOrderItem::factory()->for($order)->create(array_merge($commonData, ['status' => 11, 'shelf_id' => $shelf2->id]));
        MarketplaceOrderItem::factory()->for($order)->create(array_merge($commonData, ['status' => 10, 'shelf_id' => $shelf1->id]));

        $request = new Request([
            'comment' => 'Test Inventory',
            'inventory_shelf' => $shelf1->id,
        ]);

        $result = $this->inventoryService->createInventory($request);

        $this->assertTrue($result);
        $this->assertDatabaseHas('inventory_checks', ['comment' => 'Test Inventory']);
        $this->assertDatabaseCount('inventory_check_items', 2);
    }

    public function test_create_inventory_for_all_shelves()
    {
        $shelf1 = Shelf::create(['title' => 'Shelf 1']);
        $shelf2 = Shelf::create(['title' => 'Shelf 2']);

        $order = MarketplaceOrder::factory()->create(['status' => 1]);
        $seamstress = User::factory()->create();

        $commonData = ['price' => 100, 'seamstress_id' => $seamstress->id];

        MarketplaceOrderItem::factory()->count(2)->for($order)->create(array_merge($commonData, ['status' => 11, 'shelf_id' => $shelf1->id]));
        MarketplaceOrderItem::factory()->for($order)->create(array_merge($commonData, ['status' => 13, 'shelf_id' => $shelf2->id]));
        MarketplaceOrderItem::factory()->for($order)->create(array_merge($commonData, ['status' => 10, 'shelf_id' => $shelf1->id]));

        $request = new Request([
            'comment' => 'Test All Shelves',
            'inventory_shelf' => 'all',
        ]);

        $result = $this->inventoryService->createInventory($request);

        $this->assertTrue($result);
        $this->assertDatabaseHas('inventory_checks', ['comment' => 'Test All Shelves']);
        $this->assertDatabaseCount('inventory_check_items', 3);
    }
}
