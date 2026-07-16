<?php

use App\Models\Hanger;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper to get or create admin role
function adminRole(): Role
{
    return Role::firstOrCreate(['name' => 'admin']);
}

// Helper to get or create cutter role
function cutterRole(): Role
{
    return Role::firstOrCreate(['name' => 'cutter']);
}

// Helper to get or create seamstress role
function seamstressRole(): Role
{
    return Role::firstOrCreate(['name' => 'seamstress']);
}

test('admin can view hangers index', function () {
    $admin = User::factory()->create(['role_id' => adminRole()->id]);

    $response = $this->actingAs($admin)
        ->get(route('hangers.index'));

    $response->assertOk();
});

test('admin can create hanger', function () {
    $admin = User::factory()->create(['role_id' => adminRole()->id]);

    $response = $this->actingAs($admin)
        ->post(route('hangers.store'), [
            'title' => 'Test Hanger',
        ]);

    $response->assertRedirect(route('hangers.index'));
    $this->assertDatabaseHas('hangers', [
        'title' => 'Test Hanger',
    ]);
});

test('admin hanger store validates title required', function () {
    $admin = User::factory()->create(['role_id' => adminRole()->id]);

    $response = $this->actingAs($admin)
        ->post(route('hangers.store'), [
            'title' => '',
        ]);

    $response->assertSessionHasErrors(['title']);
});

test('admin hanger store validates title min 2 characters', function () {
    $admin = User::factory()->create(['role_id' => adminRole()->id]);

    $response = $this->actingAs($admin)
        ->post(route('hangers.store'), [
            'title' => 'a',
        ]);

    $response->assertSessionHasErrors(['title']);
});

test('admin can update hanger', function () {
    $admin = User::factory()->create(['role_id' => adminRole()->id]);
    $hanger = Hanger::factory()->create(['title' => 'Original Title']);

    $response = $this->actingAs($admin)
        ->put(route('hangers.update', $hanger), [
            'title' => 'Updated Title',
        ]);

    $response->assertRedirect(route('hangers.index'));
    $this->assertDatabaseHas('hangers', [
        'id' => $hanger->id,
        'title' => 'Updated Title',
    ]);
});

test('admin can delete hanger without orders', function () {
    $admin = User::factory()->create(['role_id' => adminRole()->id]);
    $hanger = Hanger::factory()->create();

    $response = $this->actingAs($admin)
        ->delete(route('hangers.destroy', $hanger));

    $response->assertRedirect(route('hangers.index'));
    $this->assertDatabaseMissing('hangers', [
        'id' => $hanger->id,
    ]);
});

test('admin cannot delete hanger with linked orders', function () {
    $admin = User::factory()->create(['role_id' => adminRole()->id]);
    $hanger = Hanger::factory()->create();

    // Create order with item linked to hanger
    $order = MarketplaceOrder::factory()->create(['status' => 1]);
    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $order->id,
        'hanger_id' => $hanger->id,
    ]);

    $response = $this->actingAs($admin)
        ->delete(route('hangers.destroy', $hanger));

    $response->assertRedirect(route('hangers.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('hangers', [
        'id' => $hanger->id,
    ]);
});

test('non admin cannot view hangers index', function () {
    $seamstress = User::factory()->create(['role_id' => seamstressRole()->id]);

    $response = $this->actingAs($seamstress)
        ->get(route('hangers.index'));

    $response->assertForbidden();
});

test('cutter can set hanger in their profile', function () {
    $cutter = User::factory()->create(['role_id' => cutterRole()->id]);
    $hanger = Hanger::factory()->create();

    $response = $this->actingAs($cutter)
        ->put(route('marketplace_order_items.setHanger'), [
            'hanger_id' => $hanger->id,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'id' => $cutter->id,
        'hanger_id' => $hanger->id,
    ]);
});

test('cutter can clear hanger from their profile', function () {
    $cutter = User::factory()->create([
        'role_id' => cutterRole()->id,
        'hanger_id' => Hanger::factory()->create()->id,
    ]);

    $response = $this->actingAs($cutter)
        ->put(route('marketplace_order_items.setHanger'), [
            'hanger_id' => null,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'id' => $cutter->id,
        'hanger_id' => null,
    ]);
});

test('non cutter cannot set hanger', function () {
    $seamstress = User::factory()->create(['role_id' => seamstressRole()->id]);
    $hanger = Hanger::factory()->create();

    $response = $this->actingAs($seamstress)
        ->put(route('marketplace_order_items.setHanger'), [
            'hanger_id' => $hanger->id,
        ]);

    $response->assertStatus(403);
});

test('marketplace order item show displays hanger', function () {
    $admin = User::factory()->create(['role_id' => adminRole()->id]);
    $hanger = Hanger::factory()->create(['title' => 'TestHanger123']);
    $order = MarketplaceOrder::factory()->create(['status' => 1]);
    $item = MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $order->id,
        'hanger_id' => $hanger->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('marketplace_order_items.show', $item));

    $response->assertOk();
    $response->assertSee('TestHanger123');
});

test('marketplace order item show without hanger does not display hanger title', function () {
    $admin = User::factory()->create(['role_id' => adminRole()->id]);
    $hanger = Hanger::factory()->create(['title' => 'SomeHanger']);
    $order = MarketplaceOrder::factory()->create(['status' => 1]);
    $item = MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $order->id,
        'hanger_id' => null,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('marketplace_order_items.show', $item));

    $response->assertOk();
    $response->assertDontSee('SomeHanger');
});
