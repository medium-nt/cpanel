<?php

use App\Models\MarketplaceOrderItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Создаёт пользователя-админа (роль 'admin'). */
function deleteAdminUser(): User
{
    $role = Role::firstOrCreate(['name' => 'admin']);

    return User::factory()->create(['role_id' => $role->id]);
}

/** Создаёт пользователя-кладовщика (роль 'storekeeper'). */
function deleteStorekeeperUser(): User
{
    $role = Role::firstOrCreate(['name' => 'storekeeper']);

    return User::factory()->create(['role_id' => $role->id]);
}

/** Создаёт пользователя-швею (роль 'seamstress'). */
function deleteSeamstressUser(): User
{
    $role = Role::firstOrCreate(['name' => 'seamstress']);

    return User::factory()->create(['role_id' => $role->id]);
}

/** Создаёт пользователя-менеджера (роль 'manager'). */
function deleteManagerUser(): User
{
    $role = Role::firstOrCreate(['name' => 'manager']);

    return User::factory()->create(['role_id' => $role->id]);
}

test('admin can delete item with status 11', function () {
    $item = MarketplaceOrderItem::factory()->create(['status' => 11]);

    $response = $this->actingAs(deleteAdminUser())
        ->post(route('warehouse_of_item.delete', ['marketplace_item' => $item]));

    $response->assertRedirect();
    expect(session('success'))->toContain("Товар #{$item->id} удалён с полки хранения.");

    $this->assertDatabaseHas('marketplace_order_items', [
        'id' => $item->id,
        'status' => 17,
    ]);
});

test('storekeeper cannot delete item', function () {
    $item = MarketplaceOrderItem::factory()->create(['status' => 11]);

    $response = $this->actingAs(deleteStorekeeperUser())
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

    $response = $this->actingAs(deleteSeamstressUser())
        ->post(route('warehouse_of_item.delete', ['marketplace_item' => $item]));

    $response->assertStatus(403);

    // Статус не изменился
    $this->assertDatabaseHas('marketplace_order_items', [
        'id' => $item->id,
        'status' => 11,
    ]);
});

test('manager cannot delete item', function () {
    $item = MarketplaceOrderItem::factory()->create(['status' => 11]);

    $response = $this->actingAs(deleteManagerUser())
        ->post(route('warehouse_of_item.delete', ['marketplace_item' => $item]));

    $response->assertStatus(403);

    // Статус не изменился
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

test('delete button visible to admin for status 11 items', function () {
    $item = MarketplaceOrderItem::factory()->create(['status' => 11]);

    $response = $this->actingAs(deleteAdminUser())
        ->get(route('warehouse_of_item.index'));

    $response->assertOk();
    $response->assertSee('Удалить');
    $response->assertSee(route('warehouse_of_item.delete', ['marketplace_item' => $item]));
});

test('delete button not visible to storekeeper for status 11 items', function () {
    $item = MarketplaceOrderItem::factory()->create(['status' => 11]);

    $response = $this->actingAs(deleteStorekeeperUser())
        ->get(route('warehouse_of_item.index'));

    $response->assertOk();
    $response->assertDontSee(route('warehouse_of_item.delete', ['marketplace_item' => $item]));
});

test('delete button not visible for items with status other than 11', function () {
    // Создаём товар с другим статусом (не 11)
    $item = MarketplaceOrderItem::factory()->create(['status' => 10]);

    $response = $this->actingAs(deleteAdminUser())
        ->get(route('warehouse_of_item.index'));

    $response->assertOk();

    // Проверяем, что кнопка "Удалить" не отображается для этого товара
    // (но может отображаться для других товаров со статусом 11 на странице)
    $content = $response->content();

    // Проверяем, что нет формы с действием удаления для этого конкретного товара
    expect($content)->not->toContain(route('warehouse_of_item.delete', ['marketplace_item' => $item]));
});
