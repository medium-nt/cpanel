<?php

namespace Tests\Feature\Services;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use App\Models\Shelf;
use App\Models\Shift;
use App\Models\User;
use App\Models\Workshop;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Очищаем inventory-данные — createInventory() глобально ищет order_items по статусам. */
    protected array $cleanTables = ['inventory_check_items', 'marketplace_order_items'];

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
                'unit' => 'шт',
            ]);

        $this->createMovement($material, 1, 3, 100);
        $this->createMovement($material, 2, 0, 10);

        $order = Order::factory()->create(['type_movement' => 2, 'status' => 1]);
        MovementMaterial::create([
            'material_id' => $material->id,
            'order_id' => $order->id,
            'quantity' => 20,
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

    public function test_archived_material_is_excluded_from_views()
    {
        // Create two materials: one archived, one normal
        $archivedMaterial = Material::factory()->create([
            'title' => 'Archived Material',
            'is_archive' => true,
        ]);

        $normalMaterial = Material::factory()->create([
            'title' => 'Normal Material',
            'is_archive' => false,
        ]);

        // Test defect_warehouse view
        $defectWarehouseResult = InventoryService::materialsQuantityBy('defect_warehouse');
        $defectWarehouseIds = collect($defectWarehouseResult)->pluck('material.id');
        $this->assertNotContains($archivedMaterial->id, $defectWarehouseIds);
        $this->assertContains($normalMaterial->id, $defectWarehouseIds);

        // Test warehouse view
        $warehouseResult = InventoryService::materialsQuantityBy('warehouse');
        $warehouseIds = collect($warehouseResult)->pluck('material.id');
        $this->assertNotContains($archivedMaterial->id, $warehouseIds);
        $this->assertContains($normalMaterial->id, $warehouseIds);

        // Test workshop view - create a workshop and add movements for materials
        $workshop = Workshop::factory()->create();

        // Create movements for both materials in the workshop
        $order = Order::factory()->create([
            'type_movement' => 2,
            'status' => 3,
            'workshop_id' => $workshop->id,
        ]);

        MovementMaterial::create([
            'material_id' => $archivedMaterial->id,
            'order_id' => $order->id,
            'quantity' => 100,
        ]);

        MovementMaterial::create([
            'material_id' => $normalMaterial->id,
            'order_id' => $order->id,
            'quantity' => 100,
        ]);

        $workshopResult = InventoryService::materialsQuantityBy('workshop', $workshop->id);
        $workshopIds = collect($workshopResult)->pluck('material.id');
        $this->assertNotContains($archivedMaterial->id, $workshopIds);
        $this->assertContains($normalMaterial->id, $workshopIds);
    }

    public function test_unorderable_material_is_visible_in_views()
    {
        // Create an unorderable material (is_active=0, is_archive=0)
        $unorderableMaterial = Material::factory()->create([
            'title' => 'Unorderable Material',
            'is_active' => false,
            'is_archive' => false,
        ]);

        // Test defect_warehouse view
        $defectWarehouseResult = InventoryService::materialsQuantityBy('defect_warehouse');
        $defectWarehouseIds = collect($defectWarehouseResult)->pluck('material.id');
        $this->assertContains($unorderableMaterial->id, $defectWarehouseIds);

        // Test warehouse view
        $warehouseResult = InventoryService::materialsQuantityBy('warehouse');
        $warehouseIds = collect($warehouseResult)->pluck('material.id');
        $this->assertContains($unorderableMaterial->id, $warehouseIds);

        // Test workshop view - create a workshop and add movement
        $workshop = Workshop::factory()->create();
        $order = Order::factory()->create([
            'type_movement' => 2,
            'status' => 3,
            'workshop_id' => $workshop->id,
        ]);

        MovementMaterial::create([
            'material_id' => $unorderableMaterial->id,
            'order_id' => $order->id,
            'quantity' => 100,
        ]);

        $workshopResult = InventoryService::materialsQuantityBy('workshop', $workshop->id);
        $workshopIds = collect($workshopResult)->pluck('material.id');
        $this->assertContains($unorderableMaterial->id, $workshopIds);
    }

    public function test_archived_material_excluded_from_workshop_per_shift()
    {
        // Create materials: one archived, one normal
        $archivedMaterial = Material::factory()->create([
            'title' => 'Archived Material For PerShift',
            'is_archive' => true,
        ]);

        $normalMaterial = Material::factory()->create([
            'title' => 'Normal Material For PerShift',
            'is_archive' => false,
        ]);

        // Create active shift
        $workshop = Workshop::factory()->create();
        $shift = Shift::factory()->create([
            'workshop_id' => $workshop->id,
            'status' => Shift::STATUS_ACTIVE,
        ]);

        // Create rolls in workshop for both materials
        Roll::factory()->create([
            'material_id' => $archivedMaterial->id,
            'shift_id' => $shift->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'initial_quantity' => 100.0,
        ]);

        Roll::factory()->create([
            'material_id' => $normalMaterial->id,
            'shift_id' => $shift->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'initial_quantity' => 150.0,
        ]);

        // Call the real method used by the workshop inventory page
        $result = InventoryService::materialsQuantityByWorkshopPerShift();

        // Extract material IDs from result
        $materialIds = collect($result['materials'])->pluck('material.id');

        // Archived material should NOT be in the result
        $this->assertNotContains($archivedMaterial->id, $materialIds);

        // Normal material should be in the result
        $this->assertContains($normalMaterial->id, $materialIds);
    }

    public function test_unorderable_material_visible_in_workshop_per_shift()
    {
        // Create unorderable material (is_active=0, is_archive=0) - should be visible
        $unorderableMaterial = Material::factory()->create([
            'title' => 'Unorderable Material For PerShift',
            'is_active' => false,
            'is_archive' => false,
        ]);

        // Create active shift
        $workshop = Workshop::factory()->create();
        $shift = Shift::factory()->create([
            'workshop_id' => $workshop->id,
            'status' => Shift::STATUS_ACTIVE,
        ]);

        // Create roll for unorderable material
        Roll::factory()->create([
            'material_id' => $unorderableMaterial->id,
            'shift_id' => $shift->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'initial_quantity' => 80.0,
        ]);

        // Call the method
        $result = InventoryService::materialsQuantityByWorkshopPerShift();

        // Extract material IDs
        $materialIds = collect($result['materials'])->pluck('material.id');

        // Unorderable material should be visible (not archived)
        $this->assertContains($unorderableMaterial->id, $materialIds);
    }

    public function test_can_archive_returns_true_when_no_stock()
    {
        // New material without movements should have zero stock
        $material = Material::factory()->create([
            'title' => 'New Material',
            'is_active' => false,
        ]);

        $result = InventoryService::canArchive($material);

        $this->assertTrue($result);
    }

    public function test_can_archive_returns_false_when_warehouse_has_stock()
    {
        // Create material
        $material = Material::factory()->create([
            'title' => 'Material With Stock',
            'is_active' => false,
        ]);

        // Create movement to add stock in warehouse
        $this->createMovement($material, 1, 3, 50);

        // Verify warehouse has stock
        $this->assertGreaterThan(0, InventoryService::materialInWarehouse($material->id));

        $result = InventoryService::canArchive($material);

        $this->assertFalse($result);
    }

    public function test_can_archive_returns_false_when_workshop_has_stock()
    {
        // Create material
        $material = Material::factory()->create([
            'title' => 'Material With Workshop Stock',
            'is_active' => false,
        ]);

        // Create movement to add stock in workshop
        $this->createMovement($material, 2, 3, 30);

        // Verify workshop has stock
        $this->assertGreaterThan(0, InventoryService::materialInWorkshop($material->id));

        $result = InventoryService::canArchive($material);

        $this->assertFalse($result);
    }
}
