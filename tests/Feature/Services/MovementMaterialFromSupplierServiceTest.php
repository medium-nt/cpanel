<?php

namespace Tests\Feature\Services;

use App\Http\Requests\StoreMovementMaterialFromSupplierRequest;
use App\Http\Requests\UpdateMovementMaterialFromSupplierRequest;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Role;
use App\Models\Roll;
use App\Models\Supplier;
use App\Models\User;
use App\Services\MovementMaterialFromSupplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;

class MovementMaterialFromSupplierServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $storekeeper;

    private User $admin;

    private Supplier $supplier;

    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles only if they don't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $storekeeperRole = Role::firstOrCreate(['name' => 'storekeeper']);

        // Create users with different roles
        $this->storekeeper = User::factory()->create(['role_id' => $storekeeperRole->id]);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Create supplier and material
        $this->supplier = Supplier::factory()->create(['title' => 'Test Supplier']);
        $this->material = Material::factory()->create(['title' => 'Test Material', 'unit' => 'meters']);
    }

    /**
     * Test successful material movement creation with single item.
     */
    public function test_store_successfully_creates_material_movement_single_item(): void
    {
        $this->actingAs($this->storekeeper);

        $quantity = 50;
        $numberRolls = 2;
        $comment = 'Test comment for material movement';

        $request = new StoreMovementMaterialFromSupplierRequest([
            'supplier_id' => $this->supplier->id,
            'material_id' => $this->material->id,
            'quantity' => [$quantity],
            'number_rolls' => [$numberRolls],
            'comment' => $comment,
        ]);

        $result = MovementMaterialFromSupplierService::store($request);

        // Should return true for successful operation
        $this->assertTrue($result);

        // Check that order was created
        $this->assertDatabaseHas('orders', [
            'supplier_id' => $this->supplier->id,
            'storekeeper_id' => $this->storekeeper->id,
            'type_movement' => 1,
            'status' => 0,
            'comment' => $comment,
            'completed_at' => now()->format('Y-m-d H:i:s'),
        ]);

        // Check that rolls were created with correct codes
        $order = Order::where('supplier_id', $this->supplier->id)->first();
        $this->assertNotNull($order, 'Order should not be null');
        $this->assertNotNull($order->id, 'Order ID should not be null');
        $rolls = Roll::where('material_id', $this->material->id)->get();
        $this->assertCount($numberRolls, $rolls);

        foreach ($rolls as $index => $roll) {
            $expectedCode = $this->material->type_id.'-'.str_pad($roll->id, 6, '0', STR_PAD_LEFT);
            $this->assertEquals($expectedCode, $roll->roll_code);
            $this->assertEquals(Roll::STATUS_IN_STORAGE, $roll->status);
            $this->assertEquals($quantity, $roll->initial_quantity);
        }

        // Check that movement materials were created
        $movementMaterials = MovementMaterial::where('order_id', $order->id)->get();
        $this->assertCount($numberRolls, $movementMaterials);

        foreach ($movementMaterials as $movementMaterial) {
            $this->assertEquals($this->material->id, $movementMaterial->material_id);
            $this->assertEquals($quantity, $movementMaterial->quantity);
            $this->assertEquals($order->id, $movementMaterial->order_id);
        }
    }

    /**
     * Test successful material movement creation with multiple items.
     */
    public function test_store_successfully_creates_material_movement_multiple_items(): void
    {
        $this->actingAs($this->storekeeper);

        $data = [
            [
                'quantity' => 30,
                'number_rolls' => 1,
            ],
            [
                'quantity' => 50,
                'number_rolls' => 2,
            ],
        ];

        $request = new StoreMovementMaterialFromSupplierRequest([
            'supplier_id' => $this->supplier->id,
            'material_id' => $this->material->id,
            'quantity' => [$data[0]['quantity'], $data[1]['quantity']],
            'number_rolls' => [$data[0]['number_rolls'], $data[1]['number_rolls']],
            'comment' => 'Multiple items test',
        ]);

        $result = MovementMaterialFromSupplierService::store($request);

        $this->assertTrue($result);

        // Check that multiple rolls were created
        $order = Order::where('supplier_id', $this->supplier->id)->first();
        $totalRolls = array_sum(array_column($data, 'number_rolls'));
        $this->assertEquals($totalRolls, Roll::where('material_id', $this->material->id)->count());

        // Check that multiple movement materials were created
        $this->assertEquals($totalRolls, MovementMaterial::where('order_id', $order->id)->count());
    }

    /**
     * Test store method skips items with zero quantity.
     */
    public function test_store_skips_items_with_zero_quantity(): void
    {
        $this->actingAs($this->storekeeper);

        $request = new StoreMovementMaterialFromSupplierRequest([
            'supplier_id' => $this->supplier->id,
            'material_id' => $this->material->id,
            'quantity' => [50, 0, 25], // Second item has zero quantity
            'number_rolls' => [1, 1, 1],
            'comment' => 'Zero quantity test',
        ]);

        $result = MovementMaterialFromSupplierService::store($request);

        $this->assertTrue($result);

        // Should only create 2 rolls (skipping the zero quantity item)
        $order = Order::where('supplier_id', $this->supplier->id)->first();
        $this->assertEquals(2, Roll::where('material_id', $this->material->id)->count());
        $this->assertEquals(2, MovementMaterial::where('order_id', $order->id)->count());
    }

    /**
     * Test store method handles empty quantities array.
     */
    public function test_store_returns_error_with_empty_quantities(): void
    {
        $this->actingAs($this->storekeeper);

        $request = new StoreMovementMaterialFromSupplierRequest([
            'supplier_id' => $this->supplier->id,
            'material_id' => $this->material->id,
            'quantity' => [],
            'number_rolls' => [],
            'comment' => 'Empty quantities test',
        ]);

        $result = MovementMaterialFromSupplierService::store($request);

        // Should return redirect response for error
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals('Заполните правильно количество.', session('errors')->get('error')[0]);
    }

    /**
     * Test store method with different number of rolls.
     */
    public function test_store_handles_different_number_of_rolls(): void
    {
        $this->actingAs($this->storekeeper);

        $request = new StoreMovementMaterialFromSupplierRequest([
            'supplier_id' => $this->supplier->id,
            'material_id' => $this->material->id,
            'quantity' => [20, 30],
            'number_rolls' => [2, 1], // Different number of rolls for each quantity
            'comment' => 'Different rolls test',
        ]);

        $result = MovementMaterialFromSupplierService::store($request);

        $this->assertTrue($result);

        // Should create total of 3 rolls (2 + 1)
        $order = Order::where('supplier_id', $this->supplier->id)->first();
        $this->assertEquals(3, Roll::where('material_id', $this->material->id)->count());
        $this->assertEquals(3, MovementMaterial::where('order_id', $order->id)->count());
    }

    /**
     * Test successful update of material movement by admin.
     */
    public function test_update_successfully_updates_material_movement_by_admin(): void
    {
        $this->actingAs($this->admin);

        // First create an order
        $order = Order::factory()->create([
            'supplier_id' => $this->supplier->id,
            'type_movement' => 1,
            'status' => 0,
        ]);

        // Create roll and movement material
        $roll = Roll::factory()->create([
            'material_id' => $this->material->id,
            'status' => Roll::STATUS_IN_STORAGE,
            'initial_quantity' => 50,
        ]);

        $movementMaterial = MovementMaterial::factory()->create([
            'order_id' => $order->id,
            'material_id' => $this->material->id,
            'quantity' => 50,
            'roll_id' => $roll->id,
        ]);

        $newPrice = 25.50;
        $newQuantity = 75;

        $request = new UpdateMovementMaterialFromSupplierRequest([
            'id' => [$movementMaterial->id],
            'price' => [$newPrice],
            'quantity' => [$newQuantity],
            'supplier_id' => $this->supplier->id,
            'action' => 'update',
        ]);

        $result = MovementMaterialFromSupplierService::update($request, $order);

        $this->assertTrue($result);

        // Check that supplier_id was updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'supplier_id' => $this->supplier->id,
        ]);

        // Check that movement material was updated
        $this->assertDatabaseHas('movement_materials', [
            'id' => $movementMaterial->id,
            'price' => $newPrice,
            'quantity' => $newQuantity,
        ]);

        // Check that roll was updated
        $this->assertDatabaseHas('rolls', [
            'id' => $movementMaterial->roll_id,
            'initial_quantity' => $newQuantity,
        ]);
    }

    /**
     * Test update method completes order with action parameter.
     */
    public function test_update_completes_order_with_action_parameter(): void
    {
        $this->actingAs($this->admin);

        // First create an order
        $order = Order::factory()->create([
            'supplier_id' => $this->supplier->id,
            'type_movement' => 1,
            'status' => 0,
        ]);

        // Create movement material
        $movementMaterial = MovementMaterial::factory()->create([
            'order_id' => $order->id,
            'material_id' => $this->material->id,
            'quantity' => 50,
        ]);

        $request = new UpdateMovementMaterialFromSupplierRequest([
            'id' => [$movementMaterial->id],
            'price' => [20.00],
            'quantity' => [50],
            'supplier_id' => $this->supplier->id,
            'action' => 'complete',
        ]);

        $result = MovementMaterialFromSupplierService::update($request, $order);

        $this->assertTrue($result);

        // Check that order status was updated to completed (3)
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 3,
        ]);
    }

    /**
     * Test update method skips roll quantity update if roll is not in storage.
     */
    public function test_update_skips_roll_quantity_update_if_roll_not_in_storage(): void
    {
        $this->actingAs($this->admin);

        // First create an order
        $order = Order::factory()->create([
            'supplier_id' => $this->supplier->id,
            'type_movement' => 1,
            'status' => 0,
        ]);

        // Create roll and movement material
        $roll = Roll::factory()->create([
            'material_id' => $this->material->id,
            'status' => Roll::STATUS_IN_STORAGE,
            'initial_quantity' => 50,
        ]);

        $movementMaterial = MovementMaterial::factory()->create([
            'order_id' => $order->id,
            'material_id' => $this->material->id,
            'quantity' => 50,
            'roll_id' => $roll->id,
        ]);

        // Update roll status to not be in storage
        $roll->update([
            'status' => 'shipped_to_workshop',
        ]);

        $newPrice = 30.00;
        $newQuantity = 75;

        $request = new UpdateMovementMaterialFromSupplierRequest([
            'id' => [$movementMaterial->id],
            'price' => [$newPrice],
            'quantity' => [$newQuantity],
            'supplier_id' => $this->supplier->id,
            'action' => 'update',
        ]);

        $result = MovementMaterialFromSupplierService::update($request, $order);

        $this->assertTrue($result);

        // Check that movement material price was updated but quantity wasn't
        $this->assertDatabaseHas('movement_materials', [
            'id' => $movementMaterial->id,
            'price' => $newPrice,
            'quantity' => 50, // Should remain the same
        ]);

        // Check that roll quantity wasn't updated
        $this->assertDatabaseHas('rolls', [
            'id' => $movementMaterial->roll_id,
            'initial_quantity' => 50, // Should remain the same
        ]);
    }

    /**
     * Test update method handles empty material IDs or prices.
     */
    public function test_update_returns_error_with_empty_material_ids(): void
    {
        $this->actingAs($this->admin);

        $order = Order::factory()->create([
            'supplier_id' => $this->supplier->id,
            'type_movement' => 1,
            'status' => 0,
        ]);

        $request = new UpdateMovementMaterialFromSupplierRequest([
            'id' => [],
            'price' => [],
            'supplier_id' => $this->supplier->id,
            'action' => 'update',
        ]);

        $result = MovementMaterialFromSupplierService::update($request, $order);

        // Should return redirect response for error
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals('Заполните правильно материалы и цены.', session('errors')->get('error')[0]);
    }

    /**
     * Test update method with non-admin user doesn't update supplier_id.
     */
    public function test_update_with_non_admin_user_doesnt_update_supplier(): void
    {
        $this->actingAs($this->storekeeper);

        // First create an order
        $originalSupplier = Supplier::factory()->create(['title' => 'Original Supplier']);
        $newSupplier = Supplier::factory()->create(['title' => 'New Supplier']);

        $order = Order::factory()->create([
            'supplier_id' => $originalSupplier->id,
            'type_movement' => 1,
            'status' => 0,
        ]);

        // Create movement material
        $movementMaterial = MovementMaterial::factory()->create([
            'order_id' => $order->id,
            'material_id' => $this->material->id,
            'quantity' => 50,
        ]);

        $request = new UpdateMovementMaterialFromSupplierRequest([
            'id' => [$movementMaterial->id],
            'price' => [25.50],
            'quantity' => [50],
            'supplier_id' => $newSupplier->id, // Try to change supplier
            'action' => 'update',
        ]);

        $result = MovementMaterialFromSupplierService::update($request, $order);

        $this->assertTrue($result);

        // Check that supplier_id was NOT updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'supplier_id' => $originalSupplier->id, // Should remain the same
        ]);
        $this->assertDatabaseMissing('orders', [
            'id' => $order->id,
            'supplier_id' => $newSupplier->id,
        ]);
    }

    /**
     * Test store method rolls back on database exception.
     */
    public function test_store_rolls_back_on_database_exception(): void
    {
        $this->actingAs($this->storekeeper);

        // Use invalid material ID that will cause not found exception
        $invalidMaterialId = 99999; // Non-existent ID

        $request = new StoreMovementMaterialFromSupplierRequest([
            'supplier_id' => $this->supplier->id,
            'material_id' => $invalidMaterialId,
            'quantity' => [50],
            'number_rolls' => [1],
            'comment' => 'Rollback test',
        ]);

        $result = MovementMaterialFromSupplierService::store($request);

        // Should return false on error
        $this->assertFalse($result);

        // Check that no new order was created for this test (check by comment)
        $this->assertDatabaseMissing('orders', [
            'supplier_id' => $this->supplier->id,
            'comment' => 'Rollback test',
        ]);

        // Check that no rolls were created for this material
        $this->assertDatabaseMissing('rolls', [
            'material_id' => $this->material->id,
        ]);

        // Check that no movement materials were created for this supplier
        $this->assertDatabaseMissing('movement_materials', [
            'order_id' => function ($query) {
                $query->select('id')
                    ->from('orders')
                    ->where('supplier_id', $this->supplier->id);
            },
        ]);
    }

    /**
     * Test update method rolls back on database exception.
     */
    public function test_update_rolls_back_on_database_exception(): void
    {
        $this->actingAs($this->admin);

        // First create an order
        $order = Order::factory()->create([
            'supplier_id' => $this->supplier->id,
            'type_movement' => 1,
            'status' => 0,
        ]);

        // Create roll and movement material
        $roll = Roll::factory()->create([
            'material_id' => $this->material->id,
            'status' => Roll::STATUS_IN_STORAGE,
            'initial_quantity' => 50,
        ]);

        $movementMaterial = MovementMaterial::factory()->create([
            'order_id' => $order->id,
            'material_id' => $this->material->id,
            'quantity' => 50,
            'roll_id' => $roll->id,
        ]);

        // Use invalid movement material ID that will cause not found exception
        $invalidMaterialId = 99999;

        $request = new UpdateMovementMaterialFromSupplierRequest([
            'id' => [$invalidMaterialId],
            'price' => [25.50],
            'quantity' => [50],
            'supplier_id' => $this->supplier->id,
            'action' => 'update',
        ]);

        $result = MovementMaterialFromSupplierService::update($request, $order);

        // Should return false on error
        $this->assertFalse($result);

        // Check that order status wasn't updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 0, // Should remain the same
        ]);
    }

    /**
     * Test store method creates proper roll codes.
     */
    public function test_store_creates_proper_roll_codes(): void
    {
        $this->actingAs($this->storekeeper);

        $request = new StoreMovementMaterialFromSupplierRequest([
            'supplier_id' => $this->supplier->id,
            'material_id' => $this->material->id,
            'quantity' => [100],
            'number_rolls' => [3],
            'comment' => 'Roll code test',
        ]);

        $result = MovementMaterialFromSupplierService::store($request);

        $this->assertTrue($result);

        $order = Order::where('supplier_id', $this->supplier->id)->first();
        $rolls = Roll::where('order_id', $order->id)->orderBy('id')->get();

        foreach ($rolls as $roll) {
            $expectedCode = $this->material->type_id.'-'.str_pad($roll->id, 6, '0', STR_PAD_LEFT);
            $this->assertEquals($expectedCode, $roll->roll_code);
        }
    }
}
