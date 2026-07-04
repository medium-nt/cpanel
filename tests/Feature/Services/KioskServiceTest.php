<?php

namespace Tests\Feature\Services;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Role;
use App\Models\Roll;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\KioskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KioskServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Очищаем order_items — тесты фильтрации проверяют точные счётчики. */
    protected array $cleanTables = ['marketplace_order_items'];

    private User $seamstress;

    private User $cutter;

    private User $otk;

    private User $admin;

    private User $storekeeper;

    private MarketplaceItem $marketplaceItem;

    private MarketplaceOrder $marketplaceOrder;

    private Material $flyerMaterial;

    private Material $bagMaterial;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $storekeeperRole = Role::firstOrCreate(['name' => 'storekeeper']);
        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);
        $cutterRole = Role::firstOrCreate(['name' => 'cutter']);
        $otkRole = Role::firstOrCreate(['name' => 'otk']);

        // Create users with different roles
        $this->seamstress = User::factory()->create(['role_id' => $seamstressRole->id]);
        $this->cutter = User::factory()->create(['role_id' => $cutterRole->id]);
        $this->otk = User::factory()->create(['role_id' => $otkRole->id]);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->storekeeper = User::factory()->create(['role_id' => $storekeeperRole->id]);

        // Create marketplace item and order
        $this->marketplaceItem = MarketplaceItem::factory()->create(['title' => 'Test Product']);
        $this->marketplaceOrder = MarketplaceOrder::factory()->create();

        // Create materials for consumption
        $this->flyerMaterial = Material::factory()->create(['title' => 'Флаер']);
        $this->bagMaterial = Material::factory()->create(['title' => 'Пакет']);

        // Create material consumption
        MaterialConsumption::create([
            'item_id' => $this->marketplaceItem->id,
            'material_id' => $this->flyerMaterial->id,
            'quantity' => 1,
        ]);

        MaterialConsumption::create([
            'item_id' => $this->marketplaceItem->id,
            'material_id' => $this->bagMaterial->id,
            'quantity' => 1,
        ]);

        // Create default settings for tests
        $workshopId = session('kiosk_workshop_id', 1);

        Setting::updateOrCreate(['name' => 'sticking_otk'], [
            'value' => 'filter',
            'workshop_id' => null,
        ]);

        Setting::updateOrCreate(['name' => 'sticking_seamstress'], [
            'value' => 'enabled',
            'workshop_id' => null,
        ]);

        // Set up session for kiosk user
        Session::put('user_id', $this->admin->id);
    }

    #[Test]
    public function has_orders_in_work_returns_true_for_seamstress_with_active_orders(): void
    {
        // Arrange
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'seamstress_id' => $this->seamstress->id,
            'status' => 4,
            'marketplace_item_id' => $this->marketplaceItem->id,
        ]);

        // Act & Assert
        $service = new KioskService;
        $this->assertTrue($service->hasOrdersInWork($this->seamstress));
    }

    #[Test]
    public function has_orders_in_work_returns_false_for_seamstress_without_active_orders(): void
    {
        // Arrange
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'seamstress_id' => $this->seamstress->id,
            'status' => 5,
            'marketplace_item_id' => $this->marketplaceItem->id,
        ]);

        // Act & Assert
        $service = new KioskService;
        $this->assertFalse($service->hasOrdersInWork($this->seamstress));
    }

    #[Test]
    public function has_orders_in_work_returns_true_for_cutter_with_active_orders(): void
    {
        // Arrange
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'cutter_id' => $this->cutter->id,
            'status' => 7,
            'marketplace_item_id' => $this->marketplaceItem->id,
        ]);

        // Act & Assert
        $service = new KioskService;
        $this->assertTrue($service->hasOrdersInWork($this->cutter));
    }

    #[Test]
    public function has_orders_in_work_returns_false_for_cutter_without_active_orders(): void
    {
        // Arrange
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'cutter_id' => $this->cutter->id,
            'status' => 8,
            'marketplace_item_id' => $this->marketplaceItem->id,
        ]);

        // Act & Assert
        $service = new KioskService;
        $this->assertFalse($service->hasOrdersInWork($this->cutter));
    }

    #[Test]
    public function has_orders_in_work_returns_false_for_other_roles(): void
    {
        // Arrange
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'seamstress_id' => $this->seamstress->id,
            'status' => 4,
            'marketplace_item_id' => $this->marketplaceItem->id,
        ]);

        // Act & Assert
        $service = new KioskService;
        $this->assertFalse($service->hasOrdersInWork($this->otk));
        $this->assertFalse($service->hasOrdersInWork($this->admin));
    }

    #[Test]
    public function filter_consumptions_by_material_used_flyer(): void
    {
        // Arrange
        $consumptions = MaterialConsumption::with('material')->get();
        $service = new KioskService;

        // Act
        $filtered = $service->filterConsumptionsByMaterialUsed($consumptions, 'flyer');

        // Assert
        $this->assertCount(1, $filtered);
        $this->assertEquals($this->flyerMaterial->id, $filtered->first()->material_id);
    }

    #[Test]
    public function filter_consumptions_by_material_used_bag(): void
    {
        // Arrange
        $consumptions = MaterialConsumption::with('material')->get();
        $service = new KioskService;

        // Act
        $filtered = $service->filterConsumptionsByMaterialUsed($consumptions, 'bag');

        // Assert
        $this->assertCount(1, $filtered);
        $this->assertEquals($this->bagMaterial->id, $filtered->first()->material_id);
    }

    #[Test]
    public function filter_consumptions_by_material_used_flyer_bag(): void
    {
        // Arrange
        $consumptions = MaterialConsumption::with('material')->get();
        $service = new KioskService;

        // Act
        $filtered = $service->filterConsumptionsByMaterialUsed($consumptions, 'flyer-bag');

        // Assert
        $this->assertCount(2, $filtered);
    }

    #[Test]
    public function filter_consumptions_by_material_used_invalid_type(): void
    {
        // Arrange
        $consumptions = MaterialConsumption::with('material')->get();
        $service = new KioskService;

        // Act
        $filtered = $service->filterConsumptionsByMaterialUsed($consumptions, 'invalid');

        // Assert
        $this->assertCount(0, $filtered);
    }

    #[Test]
    public function filter_consumptions_case_insensitive(): void
    {
        // Arrange
        MaterialConsumption::create([
            'item_id' => $this->marketplaceItem->id,
            'material_id' => Material::factory()->create(['title' => 'ФЛАЕР'])->id,
            'quantity' => 1,
        ]);

        $consumptions = MaterialConsumption::with('material')->get();
        $service = new KioskService;

        // Act
        $filtered = $service->filterConsumptionsByMaterialUsed($consumptions, 'flyer');

        // Assert
        $this->assertCount(2, $filtered); // original Флаер + new ФЛАЕР
    }

    #[Test]
    public function deduct_packaging_materials_creates_order_and_movements(): void
    {
        // Arrange
        $shift = $this->createShift();
        $roll = Roll::factory()->create([
            'material_id' => $this->flyerMaterial->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'shift_id' => $shift->id,
        ]);

        $service = new KioskService;
        $comment = 'Test packaging deduction';

        // Act
        $service->deductPackagingMaterials($this->marketplaceItem, 'flyer', $comment, $shift);

        // Assert
        $order = Order::where('comment', $comment)->first();
        $this->assertNotNull($order);
        $this->assertEquals(3, $order->type_movement); // expense type
        $this->assertEquals(3, $order->status); // in progress status
        $this->assertEquals($shift->id, $order->shift_id);
        $this->assertEquals($shift->workshop_id, $order->workshop_id);

        $movement = MovementMaterial::where('order_id', $order->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals($this->flyerMaterial->id, $movement->material_id);
        $this->assertEquals(1, $movement->quantity);
        $this->assertEquals($roll->id, $movement->roll_id);
    }

    #[Test]
    public function deduct_packaging_materials_flyer_bag_creates_multiple_movements(): void
    {
        // Arrange
        $shift = $this->createShift();
        $flyerRoll = Roll::factory()->create([
            'material_id' => $this->flyerMaterial->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'shift_id' => $shift->id,
        ]);
        $bagRoll = Roll::factory()->create([
            'material_id' => $this->bagMaterial->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'shift_id' => $shift->id,
        ]);

        $service = new KioskService;
        $comment = 'Test packaging deduction both';

        // Act
        $service->deductPackagingMaterials($this->marketplaceItem, 'flyer-bag', $comment, $shift);

        // Assert
        $order = Order::where('comment', $comment)->first();
        $this->assertNotNull($order);

        $movements = MovementMaterial::where('order_id', $order->id)->get();
        $this->assertCount(2, $movements);

        $materialIds = $movements->pluck('material_id')->toArray();
        $this->assertContains($this->flyerMaterial->id, $materialIds);
        $this->assertContains($this->bagMaterial->id, $materialIds);

        $rollIds = $movements->pluck('roll_id')->toArray();
        $this->assertContains($flyerRoll->id, $rollIds);
        $this->assertContains($bagRoll->id, $rollIds);
    }

    #[Test]
    public function deduct_packaging_materials_throws_when_roll_missing(): void
    {
        // Arrange — смены есть, но рулона флаера в цехе нет
        $shift = $this->createShift();
        $service = new KioskService;

        // Assert & Act
        $this->expectException(\RuntimeException::class);
        $service->deductPackagingMaterials($this->marketplaceItem, 'flyer', 'Test', $shift);
    }

    #[Test]
    public function authorize_otk_redirects_non_otk_users(): void
    {
        // Arrange
        Session::put('user_id', $this->admin->id);
        $service = new KioskService;

        // Act & Assert — Laravel wraps redirects in HttpResponseException
        $this->expectException(\Illuminate\Http\Exceptions\HttpResponseException::class);
        $service->authorizeOtk();
    }

    #[Test]
    public function authorize_otk_allows_otk_users(): void
    {
        // Arrange
        Session::put('user_id', $this->otk->id);
        $service = new KioskService;

        // Act & Assert - should not throw exception
        $service->authorizeOtk(); // This should complete without redirecting
        $this->assertTrue(true); // If we reach here, test passed
    }

    #[Test]
    public function get_filtered_inspection_items_by_single_status(): void
    {
        // Arrange
        $orderItem1 = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'status' => 4,
            'marketplace_item_id' => $this->marketplaceItem->id,
        ]);

        $orderItem2 = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'status' => 5,
            'marketplace_item_id' => $this->marketplaceItem->id,
        ]);

        $request = new Request;
        $service = new KioskService;

        // Act
        $result = $service->getFilteredInspectionItems($request, 4);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($orderItem1->id, $result->first()->id);
    }

    #[Test]
    public function get_filtered_inspection_items_by_multiple_statuses(): void
    {
        // Arrange
        $orderItem1 = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'status' => 4,
            'marketplace_item_id' => $this->marketplaceItem->id,
        ]);

        $orderItem2 = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'status' => 5,
            'marketplace_item_id' => $this->marketplaceItem->id,
        ]);

        $request = new Request;
        $service = new KioskService;

        // Act
        $result = $service->getFilteredInspectionItems($request, [4, 5]);

        // Assert
        $this->assertCount(2, $result);
    }

    #[Test]
    public function get_filtered_inspection_items_by_material(): void
    {
        // Arrange
        $item1 = MarketplaceItem::factory()->create(['title' => 'Test Fabric']);
        $item2 = MarketplaceItem::factory()->create(['title' => 'Other Material']);

        $orderItem1 = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'status' => 4,
            'marketplace_item_id' => $item1->id,
        ]);

        $orderItem2 = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'status' => 4,
            'marketplace_item_id' => $item2->id,
        ]);

        $request = new Request(['material' => 'Test']);
        $service = new KioskService;

        // Act
        $result = $service->getFilteredInspectionItems($request, 4);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($orderItem1->id, $result->first()->id);
    }

    #[Test]
    public function get_filtered_inspection_items_by_width(): void
    {
        // Arrange
        $item1 = MarketplaceItem::factory()->create(['width' => 150]);
        $item2 = MarketplaceItem::factory()->create(['width' => 200]);

        $orderItem1 = MarketplaceOrderItem::factory()->create([
            'status' => 4,
            'marketplace_item_id' => $item1->id,
        ]);

        $orderItem2 = MarketplaceOrderItem::factory()->create([
            'status' => 4,
            'marketplace_item_id' => $item2->id,
        ]);

        $request = new Request(['width' => 150]);
        $service = new KioskService;

        // Act
        $result = $service->getFilteredInspectionItems($request, 4);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($orderItem1->id, $result->first()->id);
    }

    #[Test]
    public function get_filtered_inspection_items_by_height(): void
    {
        // Arrange
        $item1 = MarketplaceItem::factory()->create(['height' => 100]);
        $item2 = MarketplaceItem::factory()->create(['height' => 200]);

        $orderItem1 = MarketplaceOrderItem::factory()->create([
            'status' => 4,
            'marketplace_item_id' => $item1->id,
        ]);

        $orderItem2 = MarketplaceOrderItem::factory()->create([
            'status' => 4,
            'marketplace_item_id' => $item2->id,
        ]);

        $request = new Request(['height' => 100]);
        $service = new KioskService;

        // Act
        $result = $service->getFilteredInspectionItems($request, 4);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($orderItem1->id, $result->first()->id);
    }

    #[Test]
    public function get_filtered_inspection_items_order_by_desc(): void
    {
        // Arrange
        $item1 = MarketplaceItem::factory()->create(['title' => 'First Item']);
        $item2 = MarketplaceItem::factory()->create(['title' => 'Second Item']);

        $orderItem1 = MarketplaceOrderItem::factory()->create([
            'status' => 4,
            'marketplace_item_id' => $item1->id,
        ]);

        $orderItem2 = MarketplaceOrderItem::factory()->create([
            'status' => 4,
            'marketplace_item_id' => $item2->id,
        ]);

        $request = new Request;
        $service = new KioskService;

        // Act
        $result = $service->getFilteredInspectionItems($request, 4, true);

        // Assert
        $this->assertEquals($orderItem2->id, $result->first()->id); // Should be ordered by id desc
    }

    #[Test]
    public function has_packaging_materials_returns_true_when_materials_available(): void
    {
        // Arrange — рулон флаера в цехе текущей смены
        $shift = $this->createShift();
        Roll::factory()->create([
            'material_id' => $this->flyerMaterial->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'shift_id' => $shift->id,
        ]);

        $service = new KioskService;

        // Act & Assert
        $this->assertTrue($service->hasPackagingMaterials($this->marketplaceItem, 'flyer', $shift));
    }

    #[Test]
    public function has_packaging_materials_returns_false_when_materials_unavailable(): void
    {
        // Arrange — смены есть, но рулона в цехе нет
        $shift = $this->createShift();
        $service = new KioskService;

        // Act & Assert
        $this->assertFalse($service->hasPackagingMaterials($this->marketplaceItem, 'flyer', $shift));
    }

    #[Test]
    public function has_packaging_materials_returns_false_for_other_shift(): void
    {
        // Arrange — рулон в смене A, проверяем по смене B
        $shiftA = $this->createShift();
        $shiftB = $this->createShift();
        Roll::factory()->create([
            'material_id' => $this->flyerMaterial->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'shift_id' => $shiftA->id,
        ]);

        $service = new KioskService;

        // Act & Assert
        $this->assertFalse($service->hasPackagingMaterials($this->marketplaceItem, 'flyer', $shiftB));
    }

    #[Test]
    public function can_use_filter_returns_true_for_admin(): void
    {
        // Arrange
        $service = new KioskService;

        // Act & Assert
        $this->assertTrue($service::canUseFilter($this->admin));
    }

    #[Test]
    public function can_use_filter_returns_true_for_storekeeper(): void
    {
        // Arrange
        $service = new KioskService;

        // Act & Assert
        $this->assertTrue($service::canUseFilter($this->storekeeper));
    }

    #[Test]
    public function can_use_filter_returns_true_for_otk_when_setting_enabled(): void
    {
        // Arrange
        Setting::updateOrCreate([
            'name' => 'sticking_otk',
        ], [
            'value' => 'filter',
            'workshop_id' => session('kiosk_workshop_id', 1),
        ]);

        $workshopId = session('kiosk_workshop_id', 1);
        Session::put('kiosk_workshop_id', $workshopId);

        $service = new KioskService;

        // Act & Assert
        $this->assertTrue($service::canUseFilter($this->otk));
    }

    #[Test]
    public function can_use_filter_returns_false_for_otk_when_setting_disabled(): void
    {
        // Arrange
        Setting::updateOrCreate([
            'name' => 'sticking_otk',
        ], [
            'value' => 'disabled',
            'workshop_id' => session('kiosk_workshop_id', 1),
        ]);

        $service = new KioskService;

        // Act & Assert
        $this->assertFalse($service::canUseFilter($this->otk));
    }

    #[Test]
    public function can_use_filter_returns_true_for_seamstress_when_setting_enabled(): void
    {
        // Arrange
        Setting::updateOrCreate([
            'name' => 'sticking_seamstress',
        ], [
            'value' => 'filter',
            'workshop_id' => session('kiosk_workshop_id', 1),
        ]);

        $workshopId = session('kiosk_workshop_id', 1);
        Session::put('kiosk_workshop_id', $workshopId);

        $service = new KioskService;

        // Act & Assert
        $this->assertTrue($service::canUseFilter($this->seamstress));
    }

    #[Test]
    public function can_use_filter_returns_false_for_other_roles(): void
    {
        // Arrange — disable filter for OTK and seamstress
        Setting::updateOrCreate(['name' => 'sticking_otk'], [
            'value' => 'disabled',
            'workshop_id' => null,
        ]);
        Setting::updateOrCreate(['name' => 'sticking_seamstress'], [
            'value' => 'disabled',
            'workshop_id' => null,
        ]);

        $service = new KioskService;

        // Act & Assert
        $this->assertFalse($service::canUseFilter($this->cutter));
        $this->assertFalse($service::canUseFilter($this->otk));
        $this->assertFalse($service::canUseFilter($this->seamstress));
    }

    #[Test]
    public function can_sticking_returns_true_for_admin(): void
    {
        // Arrange
        $service = new KioskService;

        // Act & Assert
        $this->assertTrue($service::canSticking($this->admin));
    }

    #[Test]
    public function can_sticking_returns_true_for_storekeeper(): void
    {
        // Arrange
        $service = new KioskService;

        // Act & Assert
        $this->assertTrue($service::canSticking($this->storekeeper));
    }

    #[Test]
    public function can_sticking_returns_true_for_otk_when_enabled(): void
    {
        // Arrange
        Setting::updateOrCreate([
            'name' => 'sticking_otk',
        ], [
            'value' => 'enabled',
            'workshop_id' => session('kiosk_workshop_id', 1),
        ]);

        $service = new KioskService;

        // Act & Assert
        $this->assertTrue($service::canSticking($this->otk));
    }

    #[Test]
    public function can_sticking_returns_false_for_otk_when_disabled(): void
    {
        // Arrange
        Setting::updateOrCreate([
            'name' => 'sticking_otk',
        ], [
            'value' => 'disabled',
            'workshop_id' => session('kiosk_workshop_id', 1),
        ]);

        $workshopId = session('kiosk_workshop_id', 1);
        Session::put('kiosk_workshop_id', $workshopId);

        $service = new KioskService;

        // Act & Assert
        $this->assertFalse($service::canSticking($this->otk));
    }

    #[Test]
    public function can_sticking_returns_true_for_seamstress_when_no_otk_on_shift_and_enabled(): void
    {
        // Arrange
        Setting::updateOrCreate([
            'name' => 'sticking_seamstress',
        ], [
            'value' => 'enabled',
            'workshop_id' => session('kiosk_workshop_id', 1),
        ]);

        // Ensure no OTK users are on shift
        \App\Models\User::query()->where('role_id', 5)->update(['shift_is_open' => false]);

        $workshopId = session('kiosk_workshop_id', 1);
        Session::put('kiosk_workshop_id', $workshopId);

        $service = new KioskService;

        // Act & Assert
        $this->assertTrue($service::canSticking($this->seamstress));
    }

    #[Test]
    public function can_sticking_returns_false_for_seamstress_when_otk_on_shift(): void
    {
        // Arrange — OTK user with open shift in the kiosk workshop
        $workshopId = session('kiosk_workshop_id', 1);
        Session::put('kiosk_workshop_id', $workshopId);

        $otkOnShift = User::factory()->create([
            'role_id' => 5,
            'shift_is_open' => true,
        ]);

        $shift = \App\Models\Shift::create([
            'name' => 'Test Shift',
            'status' => 1,
            'workshop_id' => $workshopId,
        ]);
        $otkOnShift->shifts()->attach($shift->id, ['effective_from' => now()->toDateString()]);

        Setting::updateOrCreate([
            'name' => 'sticking_seamstress',
        ], [
            'value' => 'enabled',
            'workshop_id' => $workshopId,
        ]);

        $service = new KioskService;

        // Act & Assert
        $this->assertFalse($service::canSticking($this->seamstress));
    }

    #[Test]
    public function can_sticking_returns_false_for_seamstress_when_setting_disabled(): void
    {
        // Arrange
        Setting::updateOrCreate([
            'name' => 'sticking_seamstress',
        ], [
            'value' => 'disabled',
            'workshop_id' => session('kiosk_workshop_id', 1),
        ]);

        $workshopId = session('kiosk_workshop_id', 1);
        Session::put('kiosk_workshop_id', $workshopId);

        $service = new KioskService;

        // Act & Assert
        $this->assertFalse($service::canSticking($this->seamstress));
    }

    #[Test]
    public function can_sticking_returns_false_for_other_roles(): void
    {
        // Arrange
        $service = new KioskService;

        // Act & Assert
        $this->assertFalse($service::canSticking($this->cutter));
    }

    /**
     * Создать смену со связанным цехом для тестов списания упаковки.
     */
    private function createShift(): Shift
    {
        return Shift::factory()->create();
    }
}
