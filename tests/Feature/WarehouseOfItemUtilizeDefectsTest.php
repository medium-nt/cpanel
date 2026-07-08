<?php

use App\Models\MarketplaceOrderItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Создаёт пользователя-админа (роль 'admin'). */
function adminUser(): User
{
    $role = Role::firstOrCreate(['name' => 'admin']);

    return User::factory()->create(['role_id' => $role->id]);
}

test('admin can utilize all items with status 16', function () {
    // Чистим seeded товары со статусом 16 для предсказуемого count
    MarketplaceOrderItem::where('status', 16)->delete();

    $items = MarketplaceOrderItem::factory()->count(3)->create(['status' => 16]);
    $itemIds = $items->pluck('id')->all();

    $response = $this->actingAs(adminUser())
        ->post(route('warehouse_of_item.status_change_scan.utilize_defects'));

    $response->assertRedirect();
    expect(session('success'))->toContain('Утилизировано товаров: 3');

    foreach ($itemIds as $id) {
        $this->assertDatabaseHas('marketplace_order_items', [
            'id' => $id,
            'status' => 17,
        ]);
    }
});

test('non admin user gets blocked even with gate permission', function () {
    $items = MarketplaceOrderItem::factory()->count(2)->create(['status' => 16]);
    $itemIds = $items->pluck('id')->all();

    $role = Role::firstOrCreate(['name' => 'storekeeper']);
    $user = User::factory()->create(['role_id' => $role->id]);

    $response = $this->actingAs($user)
        ->post(route('warehouse_of_item.status_change_scan.utilize_defects'));

    $response->assertRedirect();
    expect(session('error'))->toBe('Действие доступно только администратору.');

    // Статус не изменился
    foreach ($itemIds as $id) {
        $this->assertDatabaseHas('marketplace_order_items', [
            'id' => $id,
            'status' => 16,
        ]);
    }
});

test('error when no items with status 16 exist', function () {
    // Чистим все seeded товары со статусом 16, чтобы контроллер гарантированно нашёл 0
    MarketplaceOrderItem::where('status', 16)->delete();

    $response = $this->actingAs(adminUser())
        ->post(route('warehouse_of_item.status_change_scan.utilize_defects'));

    $response->assertRedirect();
    expect(session('error'))->toBe('Нет товаров для утилизации.');
});

test('guest is redirected to login', function () {
    $item = MarketplaceOrderItem::factory()->create(['status' => 16]);

    $response = $this->post(route('warehouse_of_item.status_change_scan.utilize_defects'));

    $response->assertRedirect('/login');

    // Товар не тронут
    $this->assertDatabaseHas('marketplace_order_items', [
        'id' => $item->id,
        'status' => 16,
    ]);
});
