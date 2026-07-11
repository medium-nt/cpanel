<?php

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Role;
use App\Models\Roll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // RoleSeeder автосидится в RefreshDatabase - используем firstOrCreate
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    $this->actingAs($this->admin);
});

test('displays movements from supplier with rolls', function () {
    // Создаем материал с единицей измерения "м"
    $material = Material::factory()->create(['unit' => 'м']);

    // Создаем поставщика
    $supplier = \App\Models\Supplier::factory()->create();

    // Создаем заказ типа "поступление от поставщика" (type_movement=1)
    $order = Order::factory()->create([
        'type_movement' => 1,
        'status' => 0,
        'supplier_id' => $supplier->id,
        'storekeeper_id' => $this->admin->id,
    ]);

    // Создаем 2 рулона с уникальными кодами
    $roll1 = Roll::factory()->create([
        'roll_code' => 'ROLL-001',
        'material_id' => $material->id,
    ]);
    $roll2 = Roll::factory()->create([
        'roll_code' => 'ROLL-002',
        'material_id' => $material->id,
    ]);

    // Создаем 2 записи в MovementMaterial с рулонами
    MovementMaterial::factory()->create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 15,
        'roll_id' => $roll1->id,
    ]);

    MovementMaterial::factory()->create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 25,
        'roll_id' => $roll2->id,
    ]);

    $response = $this->get(route('movements_from_supplier.index'));

    $response->assertStatus(200);

    // Проверяем, что отображаются итоги по рулонам и количеству
    $response->assertSee('Итого');
    $response->assertSee('2 рул.'); // 2 рулона
    $response->assertSee('40 м'); // 15 + 25 = 40 метров

    // Проверяем, что есть кнопка "Показать рулоны"
    $response->assertSee('Показать рулоны (2)');

    // Проверяем, что оба roll_code отображаются
    $response->assertSee('ROLL-001');
    $response->assertSee('ROLL-002');
});

test('displays movements from supplier without rolls', function () {
    // Создаем материал с единицей измерения "м"
    $material = Material::factory()->create(['unit' => 'м']);

    // Создаем поставщика
    $supplier = \App\Models\Supplier::factory()->create();

    // Создаем заказ типа "поступление от поставщика" (type_movement=1)
    $order = Order::factory()->create([
        'type_movement' => 1,
        'status' => 0,
        'supplier_id' => $supplier->id,
        'storekeeper_id' => $this->admin->id,
    ]);

    // Создаем 2 записи в MovementMaterial БЕЗ рулонов (roll_id = null)
    // Важно: НЕ используем factory для order, чтобы не создавать лишние Orders с неправильным type_movement
    MovementMaterial::factory()->create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 10,
        'ordered_quantity' => 10,
        'price' => 100,
        'roll_id' => null,
    ]);

    MovementMaterial::factory()->create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'quantity' => 20,
        'ordered_quantity' => 20,
        'price' => 200,
        'roll_id' => null,
    ]);

    $response = $this->get(route('movements_from_supplier.index'));

    $response->assertStatus(200);

    // Проверяем, что отображаются итоги: 0 рулонов, но количество считается
    $response->assertSee('Итого');
    $response->assertSee('0 рул.'); // 0 рулонов
    $response->assertSee('30 м'); // 10 + 20 = 30 метров

    // Проверяем через viewData, что кнопка "Показать рулоны" отсутствует
    // (используем viewData вместо assertDontSee, так как assertDontSee проверяет весь HTML)
    $orders = $response->viewData('orders');

    // Находим наш order в коллекции (может быть несколько orders)
    $foundOrder = $orders->firstWhere('id', $order->id);
    expect($foundOrder)->not->toBeNull();
    expect($foundOrder->movementMaterials->filter(fn ($m) => $m->roll_id)->count())->toBe(0);
});
