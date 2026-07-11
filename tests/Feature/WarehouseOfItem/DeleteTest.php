<?php

use App\Models\MarketplaceOrderItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Создаёт пользователя-кладовщика (роль 'storekeeper'). */
function storekeeperUser(): User
{
    $role = Role::firstOrCreate(['name' => 'storekeeper']);

    return User::factory()->create(['role_id' => $role->id]);
}

/** Создаёт пользователя-швею (роль 'seamstress'). */
function seamstressUser(): User
{
    $role = Role::firstOrCreate(['name' => 'seamstress']);

    return User::factory()->create(['role_id' => $role->id]);
}

/** Создаёт пользователя-менеджера (роль 'manager'). */
function managerUser(): User
{
    $role = Role::firstOrCreate(['name' => 'manager']);

    return User::factory()->create(['role_id' => $role->id]);
}

test('admin can delete item with status 11', function () {
    $item = MarketplaceOrderItem::factory()->create(['status' => 11]);

    $response = $this->actingAs(adminUser())
        ->post(route('warehouse_of_item.delete', ['marketplace_item' => $item]));

    $response->assertRedirect();
    expect(session('success'))->toBe("Товар #{$item->id} удалён с полки хранения.");

    $this->assertDatabaseHas('marketplace_order_items', [
        'id' => $item->id,
        'status' => 17,
    ]);
});

test('storekeeper cannot delete item', function () {
    $item = MarketplaceOrderItem::factory()->create(['status' => 11]);

    $response = $this->actingAs(storekeeperUser())
        ->post(route('warehouse_of_item.delete', ['marketplace_item' => $item]));

    $response->assertStatus(403);

    // Статус не изменился
    $this->assertDatabaseHas('marketplace_order_items', [
        'id' => $item->id,
        'status' => 11,
    ]);
});

test('seamstress cannot delete item', function () {
    $item = MarketplaceOrderItem::factory()->create(['status' => 11]);

    $response = $this->actingAs(seamstressUser())
        ->post(route('warehouse_of_item.delete', ['marketplace_item' => $item]));

    $response->assertStatus(403);

    $this->assertDatabaseHas('marketplace_order_items', [
        'id' => $item->id,
        'status' => 11,
    ]);
});

test('manager cannot delete item', function () {
    $item = MarketplaceOrderItem::factory()->create(['status' => 11]);

    $response = $this->actingAs(managerUser())
        ->post(route('warehouse_of_item.delete', ['marketplace_item' => $item]));

    $response->assertStatus(403);

    $this->assertDatabaseHas('marketplace_order_items', [
        'id' => $item->id,
        'status' => 11,
    ]);
});

test('guest is redirected to login', function () {
    $item = MarketplaceOrderItem::factory()->create(['status' => 11]);

    $response = $this->post(route('warehouse_of_item.delete', ['marketplace_item' => $item]));

    $response->assertRedirect('/login');

    // Товар не тронут
    $this->assertDatabaseHas('marketplace_order_items', [
        'id' => $item->id,
        'status' => 11,
    ]);
});
