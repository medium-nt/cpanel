<?php

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Models\Role;
use App\Models\User;
use App\Services\MarketplaceOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('service delete removes order when all items are new', function () {
    $order = MarketplaceOrder::factory()->create(['status' => 0]);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $order->id,
        'status' => 0,
    ]);

    $result = MarketplaceOrderService::delete($order);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('marketplace_orders', ['id' => $order->id]);
    $this->assertDatabaseMissing('marketplace_order_items', ['marketplace_order_id' => $order->id]);
});

test('service delete does not remove order when any item is not new', function () {
    $order = MarketplaceOrder::factory()->create(['status' => 0]);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $order->id,
        'status' => 1,
    ]);

    $result = MarketplaceOrderService::delete($order);

    expect($result)->toBeFalse();
    $this->assertDatabaseHas('marketplace_orders', ['id' => $order->id]);
});

test('service deleteNewOrdersBySupply removes only new orders with new items', function () {
    $supply = MarketplaceSupply::factory()->create();

    // Новый заказ с новыми товарами — должен удалиться
    $newOrder = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'status' => 0,
    ]);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $newOrder->id,
        'status' => 0,
    ]);

    // Новый заказ, но товар не новый — не должен удалиться
    $dirtyOrder = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'status' => 0,
    ]);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $dirtyOrder->id,
        'status' => 1,
    ]);

    // Не новый заказ — не должен удаляться
    $oldOrder = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'status' => 1,
    ]);

    $result = MarketplaceOrderService::deleteNewOrdersBySupply($supply->id);

    expect($result)->toBe([
        'deleted' => 1,
        'skipped' => 1,
    ]);

    $this->assertDatabaseMissing('marketplace_orders', ['id' => $newOrder->id]);
    $this->assertDatabaseHas('marketplace_orders', ['id' => $dirtyOrder->id]);
    $this->assertDatabaseHas('marketplace_orders', ['id' => $oldOrder->id]);
});

test('destroyNewBySupply endpoint requires admin', function () {
    $supply = MarketplaceSupply::factory()->create();

    $managerRole = Role::firstOrCreate(['name' => 'manager']);
    $manager = User::factory()->create(['role_id' => $managerRole->id]);

    $response = $this->actingAs($manager)
        ->delete(route('marketplace_orders.destroy_new_by_supply', $supply));

    $response->assertStatus(403);
});

test('destroyNewBySupply endpoint deletes new orders for admin', function () {
    $supply = MarketplaceSupply::factory()->create();

    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $order = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'status' => 0,
    ]);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $order->id,
        'status' => 0,
    ]);

    $response = $this->actingAs($admin)
        ->delete(route('marketplace_orders.destroy_new_by_supply', $supply));

    $response->assertRedirect();
    $this->assertDatabaseMissing('marketplace_orders', ['id' => $order->id]);
});
