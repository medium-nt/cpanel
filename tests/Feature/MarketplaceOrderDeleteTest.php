<?php

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Models\Role;
use App\Models\SupplyBox;
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

test('service detachNotReadyOrdersBySupply detaches only orders without box and in progress', function () {
    $supply = MarketplaceSupply::factory()->create();

    // В работе (status=4), без короба → отвязать
    $notReady = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'box_id' => null,
        'status' => 4,
    ]);

    // В коробе, в работе → НЕ отвязывать (остаётся в поставке)
    $box = SupplyBox::create(['marketplace_supply_id' => $supply->id, 'number' => 'BOX-1']);
    $inBox = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'box_id' => $box->id,
        'status' => 4,
    ]);

    // Без короба, но новый (status=0) → НЕ отвязывать
    $newOrder = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'box_id' => null,
        'status' => 0,
    ]);

    // Без короба, другой статус (status=6 «На поставку») → НЕ отвязывать (отвязываем только status=4)
    $otherOrder = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'box_id' => null,
        'status' => 6,
    ]);

    $result = MarketplaceOrderService::detachNotReadyOrdersBySupply($supply->id);

    expect($result)->toBe(['detached' => 1]);

    $this->assertDatabaseHas('marketplace_orders', ['id' => $notReady->id, 'supply_id' => null]);
    $this->assertDatabaseHas('marketplace_orders', ['id' => $inBox->id, 'supply_id' => $supply->id]);
    $this->assertDatabaseHas('marketplace_orders', ['id' => $newOrder->id, 'supply_id' => $supply->id]);
    $this->assertDatabaseHas('marketplace_orders', ['id' => $otherOrder->id, 'supply_id' => $supply->id]);
});

test('detachNotReadyBySupply endpoint requires admin', function () {
    $supply = MarketplaceSupply::factory()->create();

    $managerRole = Role::firstOrCreate(['name' => 'manager']);
    $manager = User::factory()->create(['role_id' => $managerRole->id]);

    $response = $this->actingAs($manager)
        ->delete(route('marketplace_orders.detach_not_ready_by_supply', $supply));

    $response->assertStatus(403);
});

test('detachNotReadyBySupply endpoint detaches not ready orders for admin', function () {
    $supply = MarketplaceSupply::factory()->create();

    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $order = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'box_id' => null,
        'status' => 4,
    ]);

    $response = $this->actingAs($admin)
        ->delete(route('marketplace_orders.detach_not_ready_by_supply', $supply));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('marketplace_orders', ['id' => $order->id, 'supply_id' => null]);
});

test('service detachOnSupplyOrdersBySupply detaches only orders without box and on supply status', function () {
    $supply = MarketplaceSupply::factory()->create();

    // На поставку (status=6), без короба → отвязать
    $onSupply = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'box_id' => null,
        'status' => 6,
    ]);

    // На поставку, но в коробе → НЕ отвязывать (остаётся в поставке)
    $box = SupplyBox::create(['marketplace_supply_id' => $supply->id, 'number' => 'BOX-1']);
    $inBox = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'box_id' => $box->id,
        'status' => 6,
    ]);

    // Без короба, но в работе (status=4) → НЕ отвязывать (отвязываем только status=6)
    $notReadyOrder = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'box_id' => null,
        'status' => 4,
    ]);

    // Без короба, новый (status=0) → НЕ отвязывать
    $newOrder = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'box_id' => null,
        'status' => 0,
    ]);

    $result = MarketplaceOrderService::detachOnSupplyOrdersBySupply($supply->id);

    expect($result)->toBe(['detached' => 1]);

    $this->assertDatabaseHas('marketplace_orders', ['id' => $onSupply->id, 'supply_id' => null]);
    $this->assertDatabaseHas('marketplace_orders', ['id' => $inBox->id, 'supply_id' => $supply->id]);
    $this->assertDatabaseHas('marketplace_orders', ['id' => $notReadyOrder->id, 'supply_id' => $supply->id]);
    $this->assertDatabaseHas('marketplace_orders', ['id' => $newOrder->id, 'supply_id' => $supply->id]);
});

test('detachOnSupplyOrdersBySupply endpoint requires admin', function () {
    $supply = MarketplaceSupply::factory()->create();

    $managerRole = Role::firstOrCreate(['name' => 'manager']);
    $manager = User::factory()->create(['role_id' => $managerRole->id]);

    $response = $this->actingAs($manager)
        ->delete(route('marketplace_orders.detach_on_supply_by_supply', $supply));

    $response->assertStatus(403);
});

test('detachOnSupplyOrdersBySupply endpoint detaches on supply orders for admin', function () {
    $supply = MarketplaceSupply::factory()->create();

    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $order = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'box_id' => null,
        'status' => 6,
    ]);

    $response = $this->actingAs($admin)
        ->delete(route('marketplace_orders.detach_on_supply_by_supply', $supply));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('marketplace_orders', ['id' => $order->id, 'supply_id' => null]);
});
