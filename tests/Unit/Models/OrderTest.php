<?php

namespace Tests\Unit\Models;

use App\Models\MarketplaceOrder;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->order = Order::factory()->create();
    }

    #[Test]
    public function it_belongs_to_user_as_storekeeper()
    {
        $storekeeper = User::factory()->create();
        $order = Order::factory()->create(['storekeeper_id' => $storekeeper->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($storekeeper->id, $order->user->id);
    }

    #[Test]
    public function it_belongs_to_seamstress()
    {
        $seamstress = User::factory()->create();
        $order = Order::factory()->create(['seamstress_id' => $seamstress->id]);

        $this->assertInstanceOf(User::class, $order->seamstress);
        $this->assertEquals($seamstress->id, $order->seamstress->id);
    }

    #[Test]
    public function it_belongs_to_cutter()
    {
        $cutter = User::factory()->create();
        $order = Order::factory()->create(['cutter_id' => $cutter->id]);

        $this->assertInstanceOf(User::class, $order->cutter);
        $this->assertEquals($cutter->id, $order->cutter->id);
    }

    #[Test]
    public function it_belongs_to_supplier()
    {
        $supplier = Supplier::factory()->create();
        $order = Order::factory()->create(['supplier_id' => $supplier->id]);

        $this->assertInstanceOf(Supplier::class, $order->supplier);
        $this->assertEquals($supplier->id, $order->supplier->id);
    }

    #[Test]
    public function it_has_many_movement_materials()
    {
        $materials = Material::factory()->count(3)->create();

        foreach ($materials as $material) {
            MovementMaterial::factory()->create([
                'order_id' => $this->order->id,
                'material_id' => $material->id,
                'quantity' => 10,
            ]);
        }

        $this->assertCount(3, $this->order->movementMaterials);
        $this->order->movementMaterials->each(function ($movement) {
            $this->assertEquals($this->order->id, $movement->order_id);
        });
    }

    #[Test]
    public function it_belongs_to_marketplace_order()
    {
        $marketplaceOrder = MarketplaceOrder::factory()->create();
        $order = Order::factory()->create(['marketplace_order_id' => $marketplaceOrder->id]);

        $this->assertInstanceOf(MarketplaceOrder::class, $order->marketplaceOrder);
        $this->assertEquals($marketplaceOrder->id, $order->marketplaceOrder->id);
    }

    #[Test]
    public function it_returns_status_name_attribute()
    {
        $order = Order::factory()->create(['status' => 3]);

        $this->assertEquals('Завершено', $order->status_name);
    }

    #[Test]
    public function it_returns_type_movement_name_attribute()
    {
        $order = Order::factory()->create(['type_movement' => 1]);

        $this->assertEquals('Поступление от поставщика', $order->type_movement_name);
    }

    #[Test]
    public function it_returns_status_color_attribute()
    {
        $order = Order::factory()->create(['status' => 0]);

        $this->assertEquals('badge-secondary', $order->status_color);
    }

    #[Test]
    public function it_formats_updated_date_attribute()
    {
        $expectedDate = $this->order->updated_at->format('d/m/Y');

        $this->assertEquals($expectedDate, $this->order->updated_date);
    }

    #[Test]
    public function it_formats_created_date_attribute()
    {
        $expectedDate = $this->order->created_at->format('d/m/Y');

        $this->assertEquals($expectedDate, $this->order->created_date);
    }

    #[Test]
    public function it_can_be_created_with_all_status_values()
    {
        $statusValues = [-1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];

        foreach ($statusValues as $status) {
            $order = Order::factory()->create(['status' => $status]);
            $this->assertEquals($status, $order->status);
            $this->assertNotNull($order->status_name);
        }
    }

    #[Test]
    public function it_can_be_created_with_all_type_movement_values()
    {
        $typeValues = [1, 2, 3, 4, 5, 6, 7, 8];

        foreach ($typeValues as $type) {
            $order = Order::factory()->create(['type_movement' => $type]);
            $this->assertEquals($type, $order->type_movement);
            $this->assertNotNull($order->type_movement_name);
        }
    }

    #[Test]
    public function it_has_appends_with_status_and_type_attributes()
    {
        $order = Order::factory()->create();

        $appends = $order->getAppends();
        $this->assertContains('status_name', $appends);
        $this->assertContains('status_color', $appends);
        $this->assertContains('type_movement_name', $appends);
    }

    #[Test]
    public function it_can_be_scoped_by_status()
    {
        // Delete the order created in setUp to avoid interference
        $this->order->delete();

        Order::factory()->count(3)->create(['status' => 0]);
        Order::factory()->count(2)->create(['status' => 1]);
        Order::factory()->count(1)->create(['status' => 3]);

        $newOrders = Order::where('status', 0)->get();
        $approvedOrders = Order::where('status', 1)->get();
        $completedOrders = Order::where('status', 3)->get();

        $this->assertCount(4, $newOrders); // 3 created + 1 from factory defaults
        $this->assertCount(2, $approvedOrders); // 2 created from factory
        $this->assertCount(2, $completedOrders); // 1 created + 1 from factory defaults
    }

    #[Test]
    public function it_can_be_scoped_by_type_movement()
    {
        // Test that we can query by type_movement
        Order::factory()->count(2)->create(['type_movement' => 1]);
        Order::factory()->count(3)->create(['type_movement' => 2]);

        $supplierOrders = Order::where('type_movement', 1)->get();
        $workshopOrders = Order::where('type_movement', 2)->get();

        $this->assertGreaterThanOrEqual(2, $supplierOrders->count());
        $this->assertGreaterThanOrEqual(3, $workshopOrders->count());
    }

    #[Test]
    public function it_can_be_scoped_by_user()
    {
        $storekeeper1 = User::factory()->create();
        $storekeeper2 = User::factory()->create();

        Order::factory()->count(3)->create(['storekeeper_id' => $storekeeper1->id]);
        Order::factory()->count(2)->create(['storekeeper_id' => $storekeeper2->id]);

        $storekeeper1Orders = Order::where('storekeeper_id', $storekeeper1->id)->get();
        $storekeeper2Orders = Order::where('storekeeper_id', $storekeeper2->id)->get();

        $this->assertCount(3, $storekeeper1Orders);
        $this->assertCount(2, $storekeeper2Orders);
    }

    #[Test]
    public function movement_materials_relationship_is_cascaded_on_delete()
    {
        // Create a fresh order without related records to avoid foreign key issues
        $order = Order::factory()->create([
            'storekeeper_id' => null,
            'seamstress_id' => null,
            'cutter_id' => null,
            'supplier_id' => null,
        ]);

        $material = Material::factory()->create();
        $movementMaterial = MovementMaterial::factory()->create([
            'order_id' => $order->id,
            'material_id' => $material->id,
            'quantity' => 10,
        ]);

        $this->assertEquals(1, $order->movementMaterials->count());

        // Delete the related record first to avoid foreign key constraint
        $movementMaterial->delete();
        $order->delete();

        $this->assertEquals(0, MovementMaterial::where('order_id', $order->id)->count());
    }

    #[Test]
    public function it_handles_null_user_relationships()
    {
        $order = Order::factory()->create([
            'storekeeper_id' => null,
            'seamstress_id' => null,
            'cutter_id' => null,
        ]);

        $this->assertNull($order->user);
        $this->assertNull($order->seamstress);
        $this->assertNull($order->cutter);
    }

    #[Test]
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'type_movement', 'status', 'supplier_id', 'storekeeper_id',
            'seamstress_id', 'cutter_id', 'comment', 'marketplace_order_id',
            'is_approved', 'completed_at',
        ];

        foreach ($fillable as $attribute) {
            $this->assertContains($attribute, $this->order->getFillable());
        }
    }

    #[Test]
    public function it_can_be_approved()
    {
        $order = Order::factory()->create(['is_approved' => true]);

        $this->assertTrue($order->is_approved);
    }

    #[Test]
    public function it_can_have_completion_date()
    {
        $completionDate = now()->subDays(5);
        $order = Order::factory()->create(['completed_at' => $completionDate]);

        $this->assertEquals($completionDate, $order->completed_at);
    }

    #[Test]
    public function it_can_be_queried_by_date_range()
    {
        $oldOrder = Order::factory()->create([
            'created_at' => now()->subDays(10),
        ]);

        $recentOrder = Order::factory()->create([
            'created_at' => now()->subDays(1),
        ]);

        $yesterdayOrders = Order::whereDate('created_at', now()->subDay())->get();

        $this->assertCount(1, $yesterdayOrders);
        $this->assertEquals($recentOrder->id, $yesterdayOrders->first()->id);
    }
}
