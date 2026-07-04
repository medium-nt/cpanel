<?php

namespace Tests\Feature\Services;

use App\Http\Requests\StoreMovementMaterialToWorkshopRequest;
use App\Models\Material;
use App\Models\Order;
use App\Models\User;
use App\Services\MovementMaterialToWorkshopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MovementMaterialToWorkshopServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Очищаем marketplace_order_items — тест проверяет точный счётчик стикеров. */
    protected array $cleanTables = ['marketplace_order_items'];

    private User $user;

    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $this->seamstressRole = \App\Models\Role::firstOrCreate(['name' => 'seamstress']);
        $this->cutterRole = \App\Models\Role::firstOrCreate(['name' => 'cutter']);
        $this->otkRole = \App\Models\Role::firstOrCreate(['name' => 'otk']);
        $this->storekeeperRole = \App\Models\Role::firstOrCreate(['name' => 'storekeeper']);

        // Create test user with seamstress role
        $this->user = User::factory()->create([
            'role_id' => $this->seamstressRole->id,
        ]);

        // Create test material
        $this->material = Material::factory()->create();

        // Create workshop and shift
        $workshop = \App\Models\Workshop::factory()->create();
        $shift = \App\Models\Shift::factory()->create(['workshop_id' => $workshop->id]);

        // Assign user to shift
        $this->user->shifts()->attach($shift->id, [
            'effective_from' => now()->toDateString(),
        ]);

        // Create material workshop relation
        \App\Models\MaterialWorkshop::factory()->create([
            'material_id' => $this->material->id,
            'workshop_id' => $workshop->id,
        ]);
    }

    /**
     * Test getOrdersByStatus method with completed status.
     */
    public function test_get_orders_by_status_completed(): void
    {
        // Create completed orders specifically for this test
        Order::factory()->count(3)->create([
            'type_movement' => 2,
            'status' => 3,
            'shift_id' => $this->user->currentShift()->id,
        ]);

        $orders = MovementMaterialToWorkshopService::getOrdersByStatus('completed', $this->user);
        $ordersCollection = $orders->get();

        $this->assertCount(3, $ordersCollection);
        foreach ($ordersCollection as $order) {
            $this->assertEquals(3, $order->status);
        }
    }

    /**
     * Test getOrdersByStatus method without user parameter.
     */
    public function test_get_orders_by_status_without_user(): void
    {
        // Without a user, the shift filter is NOT applied — service returns ALL type_movement=2 orders.
        $baseline = Order::where('type_movement', 2)->count();

        Order::factory()->count(3)->create([
            'type_movement' => 2,
            'status' => 0,
        ]);

        $ordersCollection = MovementMaterialToWorkshopService::getOrdersByStatus('all')->get();

        // 'all' status = [-1, 0, 1, 2, 3]; our 3 new orders (status 0) must all be included.
        $this->assertEquals($baseline + 3, $ordersCollection->count());
    }

    /**
     * Test getOrdersByStatus method with default status.
     */
    public function test_get_orders_by_status_default(): void
    {
        // Create test orders specifically for this test
        Order::factory()->count(3)->create([
            'type_movement' => 2,
            'status' => 0,
            'shift_id' => $this->user->currentShift()->id,
        ]);
        Order::factory()->count(2)->create([
            'type_movement' => 2,
            'status' => 2,
            'shift_id' => $this->user->currentShift()->id,
        ]);

        $orders = MovementMaterialToWorkshopService::getOrdersByStatus('all', $this->user);
        $ordersCollection = $orders->get();

        $this->assertCount(5, $ordersCollection);
        foreach ($ordersCollection as $order) {
            $this->assertEquals(2, $order->type_movement);
        }
    }

    /**
     * Test store method with valid data.
     */
    public function test_store_with_valid_data(): void
    {
        Auth::login($this->user);

        $request = new StoreMovementMaterialToWorkshopRequest([
            'material_id' => $this->material->id,
            'comment' => 'Test comment',
        ]);

        $result = MovementMaterialToWorkshopService::store($request);

        $this->assertTrue($result);

        // Check order was created
        $order = Order::where('type_movement', 2)->where('status', 0)->where('seamstress_id', $this->user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals($this->user->id, $order->seamstress_id);

        // Check movement material was created
        $movementMaterial = \App\Models\MovementMaterial::where('order_id', $order->id)->first();
        $this->assertNotNull($movementMaterial);
        $this->assertEquals($this->material->id, $movementMaterial->material_id);
    }

    /**
     * Test store method with empty material_id.
     */
    public function test_store_with_empty_material_id(): void
    {
        Auth::login($this->user);

        $request = new StoreMovementMaterialToWorkshopRequest([
            'material_id' => '',
            'comment' => 'Test comment',
        ]);

        $result = MovementMaterialToWorkshopService::store($request);

        // Should return redirect response
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $result);
        $this->assertEquals($result->getSession()->get('errors')->first('error'), 'Выберите материал.');
    }

    /**
     * Test getCountNotShippedMovements method.
     */
    public function test_get_count_not_shipped_movements(): void
    {
        // Create not shipped orders for current user's shift
        Order::factory()->count(3)->create([
            'type_movement' => 2,
            'status' => 0,
            'shift_id' => $this->user->currentShift()->id,
        ]);

        // Create shipped orders
        Order::factory()->count(2)->create([
            'type_movement' => 2,
            'status' => 2,
            'shift_id' => $this->user->currentShift()->id,
        ]);

        $count = MovementMaterialToWorkshopService::getCountNotShippedMovements($this->user);

        $this->assertEquals(3, $count);
    }

    /**
     * Test getCountNotShippedMovements method without user.
     */
    public function test_get_count_not_shipped_movements_without_user(): void
    {
        // Without a user, the shift filter is NOT applied — count includes all type_movement=2, status=0 orders.
        $baseline = Order::where('type_movement', 2)->where('status', 0)->count();

        Order::factory()->count(3)->create([
            'type_movement' => 2,
            'status' => 0,
        ]);

        $count = MovementMaterialToWorkshopService::getCountNotShippedMovements();

        $this->assertEquals($baseline + 3, $count);
    }

    /**
     * Test getCountNotReceivedMovements method.
     */
    public function test_get_count_not_received_movements(): void
    {
        // Create not received orders (status 2)
        Order::factory()->count(3)->create([
            'type_movement' => 2,
            'status' => 2,
            'shift_id' => $this->user->currentShift()->id,
        ]);

        // Create shipped orders (status 0)
        Order::factory()->count(2)->create([
            'type_movement' => 2,
            'status' => 0,
            'shift_id' => $this->user->currentShift()->id,
        ]);

        $count = MovementMaterialToWorkshopService::getCountNotReceivedMovements($this->user);

        $this->assertEquals(3, $count);
    }

    /**
     * Test getStickeredMarketplaceOrderItem method.
     */
    public function test_get_stickered_marketplace_order_item(): void
    {
        // Create stickered items (status 5)
        \App\Models\MarketplaceOrderItem::factory()->count(3)->create([
            'status' => 5,
            'workshop_id' => 1,
        ]);

        // Create non-stickered items
        \App\Models\MarketplaceOrderItem::factory()->count(2)->create([
            'status' => 4,
            'workshop_id' => 1,
        ]);

        $count = MovementMaterialToWorkshopService::getStickeredMarketplaceOrderItem(1);

        $this->assertEquals(3, $count);
    }

    /**
     * Test getStickeredMarketplaceOrderItem method without workshop filter.
     */
    public function test_get_stickered_marketplace_order_item_without_workshop_filter(): void
    {
        // Create stickered items
        \App\Models\MarketplaceOrderItem::factory()->count(3)->create([
            'status' => 5,
        ]);

        $count = MovementMaterialToWorkshopService::getStickeredMarketplaceOrderItem();

        $this->assertEquals(3, $count);
    }
}
