<?php

namespace Tests\Feature;

use App\Http\Requests\StoreDefectMaterialToSupplierRequest;
use App\Http\Requests\UpdateMovementMaterialFromSupplierRequest;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\MovementDefectMaterialToSupplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;

class MovementDefectMaterialToSupplierServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $storekeeper;

    private Material $material;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $storekeeperRole = Role::firstOrCreate(['name' => 'storekeeper']);

        $this->storekeeper = User::factory()->create(['role_id' => $storekeeperRole->id]);
        $this->material = Material::factory()->create(['title' => 'Test Material 1', 'unit' => 'm']);
        $this->supplier = Supplier::factory()->create(['title' => 'Test Supplier']);
    }

    /**
     * Создать реальные остатки дефектного материала на складе.
     *
     * InventoryService::defectMaterialInWarehouse(mid) = countMaterial(mid, 4, 3) - countMaterial(mid, 5, 3),
     * где countMaterial — сумма MovementMaterial.quantity по order с заданным type_movement и status.
     * Создаём заказ прихода дефекта (type_movement=4, status=3) с указанным количеством —
     * это даёт реальное значение остатка без моков (и без contamination alias-mocking).
     *
     * @param  int  $quantity  сколько дефектного материала «лежит» на складе
     * @param  Material|null  $material  материал (по умолчанию $this->material)
     */
    private function createDefectStock(int $quantity = 10, ?Material $material = null): void
    {
        $material ??= $this->material;

        $stockOrder = Order::factory()->create([
            'type_movement' => 4,
            'status' => 3,
        ]);

        MovementMaterial::factory()->create([
            'order_id' => $stockOrder->id,
            'material_id' => $material->id,
            'quantity' => $quantity,
        ]);
    }

    public function test_store_method_creates_order_and_movement_materials(): void
    {
        $this->actingAs($this->storekeeper);
        $this->createDefectStock(10);

        $request = new StoreDefectMaterialToSupplierRequest;
        $request->setMethod('POST');
        $request->request->set('supplier_id', $this->supplier->id);
        $request->request->set('material_id', [$this->material->id]);
        $request->request->set('ordered_quantity', [5]);
        $request->request->set('comment', 'Test comment');

        $result = MovementDefectMaterialToSupplierService::store($request);

        $this->assertInstanceOf(RedirectResponse::class, $result);

        $order = Order::where('storekeeper_id', $this->storekeeper->id)
            ->where('supplier_id', $this->supplier->id)
            ->where('type_movement', 5)
            ->where('status', 3)
            ->first();

        $this->assertNotNull($order, 'Order should have been created');
        $this->assertNotNull($order->completed_at, 'Order should have completion timestamp');

        $movementMaterials = MovementMaterial::where('order_id', $order->id)
            ->where('material_id', $this->material->id)
            ->get();

        $this->assertCount(1, $movementMaterials);
        $this->assertEquals(5, $movementMaterials->first()->quantity);
    }

    public function test_store_method_handles_multiple_materials(): void
    {
        $this->actingAs($this->storekeeper);

        $material2 = Material::factory()->create(['title' => 'Test Material 2', 'unit' => 'kg']);
        $this->createDefectStock(10);
        $this->createDefectStock(10, $material2);

        $request = new StoreDefectMaterialToSupplierRequest;
        $request->setMethod('POST');
        $request->request->set('supplier_id', $this->supplier->id);
        $request->request->set('material_id', [$this->material->id, $material2->id]);
        $request->request->set('ordered_quantity', [5, 3]);
        $request->request->set('comment', 'Test multiple materials');

        $result = MovementDefectMaterialToSupplierService::store($request);

        $this->assertInstanceOf(RedirectResponse::class, $result);

        $order = Order::where('storekeeper_id', $this->storekeeper->id)
            ->where('supplier_id', $this->supplier->id)
            ->where('type_movement', 5)
            ->where('comment', 'Test multiple materials')
            ->first();

        $this->assertNotNull($order);

        $movementMaterials = MovementMaterial::where('order_id', $order->id)->get();
        $this->assertCount(2, $movementMaterials);
        $this->assertEquals(5, $movementMaterials->where('material_id', $this->material->id)->first()->quantity);
        $this->assertEquals(3, $movementMaterials->where('material_id', $material2->id)->first()->quantity);
    }

    public function test_store_method_exceeds_inventory(): void
    {
        $this->actingAs($this->storekeeper);
        $this->createDefectStock(3); // Less than requested quantity

        $request = new StoreDefectMaterialToSupplierRequest;
        $request->setMethod('POST');
        $request->request->set('supplier_id', $this->supplier->id);
        $request->request->set('material_id', [$this->material->id]);
        $request->request->set('ordered_quantity', [5]); // More than available
        $request->request->set('comment', 'Test excess quantity');

        $result = MovementDefectMaterialToSupplierService::store($request);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(
            'Невозможно списать больше материала, чем есть в наличии.',
            $result->getSession()->get('errors')?->first()
        );

        // Order rolled back
        $this->assertNull(
            Order::where('storekeeper_id', $this->storekeeper->id)->where('type_movement', 5)->first(),
            'Order should not have been created due to rollback'
        );
    }

    public function test_store_method_handles_empty_arrays(): void
    {
        $this->actingAs($this->storekeeper);

        $request = new StoreDefectMaterialToSupplierRequest;
        $request->setMethod('POST');
        $request->request->set('supplier_id', $this->supplier->id);
        $request->request->set('material_id', []);
        $request->request->set('ordered_quantity', []);
        $request->request->set('comment', 'Test empty');

        $result = MovementDefectMaterialToSupplierService::store($request);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(
            'Заполните правильно список материалов и количество.',
            $result->getSession()->get('errors')?->first()
        );

        $this->assertNull(
            Order::where('storekeeper_id', $this->storekeeper->id)->where('type_movement', 5)->first(),
            'Order should not have been created due to empty arrays'
        );
    }

    public function test_store_method_with_zero_material_id(): void
    {
        $this->actingAs($this->storekeeper);
        $this->createDefectStock(10);

        $request = new StoreDefectMaterialToSupplierRequest;
        $request->setMethod('POST');
        $request->request->set('supplier_id', $this->supplier->id);
        $request->request->set('material_id', [0, $this->material->id]); // 0 should be skipped
        $request->request->set('ordered_quantity', [0, 5]);
        $request->request->set('comment', 'Test zero material ID');

        $result = MovementDefectMaterialToSupplierService::store($request);

        $this->assertInstanceOf(RedirectResponse::class, $result);

        $order = Order::where('storekeeper_id', $this->storekeeper->id)
            ->where('type_movement', 5)
            ->where('comment', 'Test zero material ID')
            ->first();

        $this->assertNotNull($order);

        $movementMaterials = MovementMaterial::where('order_id', $order->id)->get();
        $this->assertCount(1, $movementMaterials, 'Only one movement material should be created (skipping material_id 0)');
        $this->assertEquals($this->material->id, $movementMaterials->first()->material_id);
    }

    public function test_store_method_handles_zero_quantity(): void
    {
        $this->actingAs($this->storekeeper);
        $this->createDefectStock(10);

        $request = new StoreDefectMaterialToSupplierRequest;
        $request->setMethod('POST');
        $request->request->set('supplier_id', $this->supplier->id);
        $request->request->set('material_id', [$this->material->id]);
        $request->request->set('ordered_quantity', [0]);
        $request->request->set('comment', 'Test zero quantity');

        $result = MovementDefectMaterialToSupplierService::store($request);

        $this->assertInstanceOf(RedirectResponse::class, $result);

        $order = Order::where('storekeeper_id', $this->storekeeper->id)
            ->where('type_movement', 5)
            ->where('comment', 'Test zero quantity')
            ->first();

        $this->assertNotNull($order);

        $movementMaterial = MovementMaterial::where('order_id', $order->id)
            ->where('material_id', $this->material->id)
            ->first();

        $this->assertNotNull($movementMaterial);
    }

    public function test_update_method_updates_material_prices(): void
    {
        $this->actingAs($this->storekeeper);

        $order = Order::factory()->create([
            'supplier_id' => $this->supplier->id,
            'storekeeper_id' => $this->storekeeper->id,
            'type_movement' => 5,
            'status' => 3,
        ]);

        $movementMaterial = MovementMaterial::factory()->create([
            'order_id' => $order->id,
            'material_id' => $this->material->id,
            'quantity' => 5,
            'price' => 100.00,
        ]);

        $request = new UpdateMovementMaterialFromSupplierRequest;
        $request->setMethod('POST');
        $request->request->set('id', [$movementMaterial->id]);
        $request->request->set('price', [150.50]);

        $result = MovementDefectMaterialToSupplierService::update($request, $order);

        $this->assertTrue($result);
        $this->assertEquals(150.50, $movementMaterial->fresh()->price);
    }

    public function test_update_method_handles_empty_arrays(): void
    {
        $this->actingAs($this->storekeeper);

        $order = Order::factory()->create([
            'supplier_id' => $this->supplier->id,
            'storekeeper_id' => $this->storekeeper->id,
            'type_movement' => 5,
            'status' => 3,
        ]);

        $request = new UpdateMovementMaterialFromSupplierRequest;
        $request->setMethod('POST');
        $request->request->set('id', []);
        $request->request->set('price', []);

        $result = MovementDefectMaterialToSupplierService::update($request, $order);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals(
            'Заполните правильно материалы и цены.',
            $result->getSession()->get('errors')?->first()
        );
    }
}
