<?php

namespace Tests\Feature\Services;

use App\Http\Requests\StoreRemnantsRequest;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\WriteOffRemnantService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class WriteOffRemnantServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected $seed = false;

    private User $storekeeper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storekeeper = User::factory()->create();
        $this->actingAs($this->storekeeper);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_store_successfully_writes_off_remnants()
    {
        $material = Material::factory()->create(['title' => 'Test Material', 'unit' => 'pcs']);
        $quantity = 5;
        $comment = 'Test comment';

        $inventoryMock = Mockery::mock('alias:' . InventoryService::class);
        $inventoryMock->shouldReceive('remnantsMaterialInWarehouse')->with($material->id)->andReturn($quantity + 5);

        Log::shouldReceive('channel')->with('erp')->andReturnSelf();
        Log::shouldReceive('notice');

        $request = new StoreRemnantsRequest([
            'material_id' => [$material->id],
            'ordered_quantity' => [$quantity],
            'comment' => $comment,
        ]);

        $response = WriteOffRemnantService::store($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue(session()->has('success'));

        $this->assertDatabaseHas('orders', [
            'storekeeper_id' => $this->storekeeper->id,
            'type_movement' => 8,
            'status' => 3,
            'comment' => $comment,
        ]);

        $this->assertDatabaseHas('movement_materials', [
            'material_id' => $material->id,
            'quantity' => $quantity,
        ]);
    }

    public function test_store_returns_error_if_materials_are_empty()
    {
        // ИСПРАВЛЕНО: Проверяем, что количество заказов не увеличилось
        $initialOrderCount = \App\Models\Order::count();

        $request = new StoreRemnantsRequest([
            'material_id' => [],
            'ordered_quantity' => [],
        ]);

        $response = WriteOffRemnantService::store($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals('Заполните правильно список материалов и количество.', session('errors')->get('error')[0]);
        $this->assertDatabaseCount('orders', $initialOrderCount);
    }

    public function test_store_returns_error_if_quantity_is_insufficient()
    {
        // ИСПРАВЛЕНО: Проверяем, что количество заказов не увеличилось
        $initialOrderCount = \App\Models\Order::count();

        $material = Material::factory()->create(['title' => 'Test Material', 'unit' => 'pcs']);
        $availableQuantity = 5;
        $requestedQuantity = 10;

        $inventoryMock = Mockery::mock('alias:' . InventoryService::class);
        $inventoryMock->shouldReceive('remnantsMaterialInWarehouse')->with($material->id)->andReturn($availableQuantity);

        $request = new StoreRemnantsRequest([
            'material_id' => [$material->id],
            'ordered_quantity' => [$requestedQuantity],
        ]);

        $response = WriteOffRemnantService::store($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals('Невозможно списать больше материала, чем есть в наличии.', session('errors')->get('error')[0]);
        $this->assertDatabaseCount('orders', $initialOrderCount);
    }

    public function test_store_rolls_back_on_internal_error()
    {
        // ИСПРАВЛЕНО: Проверяем, что количество записей не увеличилось
        $initialOrderCount = \App\Models\Order::count();
        $initialMovementCount = MovementMaterial::count();

        $material = Material::factory()->create(['title' => 'Test Material', 'unit' => 'pcs']);
        $quantity = 5;

        $inventoryMock = Mockery::mock('alias:' . InventoryService::class);
        $inventoryMock->shouldReceive('remnantsMaterialInWarehouse')->with($material->id)->andReturn($quantity + 5);

        Log::shouldReceive('channel')->with('erp')->andThrow(new \Exception('Log service is down'));

        $request = new StoreRemnantsRequest([
            'material_id' => [$material->id],
            'ordered_quantity' => [$quantity],
        ]);

        $response = WriteOffRemnantService::store($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue(session()->has('errors'));
        $this->assertEquals('Внутренняя ошибка', session('errors')->get('error')[0]);
        $this->assertDatabaseCount('orders', $initialOrderCount);
        $this->assertDatabaseCount('movement_materials', $initialMovementCount);
    }
}
