<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seamstress does not see date filters on in_work status', function () {
    $role = Role::firstOrCreate(['name' => 'seamstress']);
    $seamstress = User::factory()->create(['role_id' => $role->id]);

    $response = $this->actingAs($seamstress)
        ->get(route('marketplace_order_items.index', ['status' => 'in_work']));

    $response->assertStatus(200);
    $response->assertDontSee('name="date_start"', false);
    $response->assertDontSee('name="date_end"', false);
});

test('seamstress sees date filters on done status', function () {
    $role = Role::firstOrCreate(['name' => 'seamstress']);
    $seamstress = User::factory()->create(['role_id' => $role->id]);

    $response = $this->actingAs($seamstress)
        ->get(route('marketplace_order_items.index', ['status' => 'done']));

    $response->assertStatus(200);
    $response->assertSee('name="date_start"', false);
    $response->assertSee('name="date_end"', false);
});

test('cutter does not see date filters on cutting status', function () {
    $role = Role::firstOrCreate(['name' => 'cutter']);
    $cutter = User::factory()->create(['role_id' => $role->id]);

    $response = $this->actingAs($cutter)
        ->get(route('marketplace_order_items.index', ['status' => 'cutting']));

    $response->assertStatus(200);
    $response->assertDontSee('name="date_start"', false);
    $response->assertDontSee('name="date_end"', false);
});

test('cutter sees date filters on done status', function () {
    $role = Role::firstOrCreate(['name' => 'cutter']);
    $cutter = User::factory()->create(['role_id' => $role->id]);

    $response = $this->actingAs($cutter)
        ->get(route('marketplace_order_items.index', ['status' => 'done']));

    $response->assertStatus(200);
    $response->assertSee('name="date_start"', false);
    $response->assertSee('name="date_end"', false);
});

test('admin sees date filters on in_work status', function () {
    $role = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $role->id]);

    $response = $this->actingAs($admin)
        ->get(route('marketplace_order_items.index', ['status' => 'in_work']));

    $response->assertStatus(200);
    $response->assertSee('name="date_start"', false);
    $response->assertSee('name="date_end"', false);
});

test('seamstress date filters are ignored on in_work status', function () {
    $role = Role::firstOrCreate(['name' => 'seamstress']);
    $seamstress = User::factory()->create(['role_id' => $role->id]);

    // Create two items with status 'in_work' (4), assigned to the seamstress
    // and with different created_at dates
    $oldItem = \App\Models\MarketplaceOrderItem::factory()->create([
        'status' => 4, // in_work
        'seamstress_id' => $seamstress->id, // Assigned to this seamstress
        'created_at' => now()->subMonth(),
    ]);

    $recentItem = \App\Models\MarketplaceOrderItem::factory()->create([
        'status' => 4, // in_work
        'seamstress_id' => $seamstress->id, // Assigned to this seamstress
        'created_at' => now(),
    ]);

    // Set date_start to future date (should filter out both items without fix)
    $futureDate = now()->addDay()->toDateString();

    $response = $this->actingAs($seamstress)
        ->get(route('marketplace_order_items.index', [
            'status' => 'in_work',
            'date_start' => $futureDate,
        ]));

    $response->assertStatus(200);

    // Both items should be visible (date filter ignored for seamstress on in_work)
    $items = $response->viewData('items');
    $itemIds = collect($items->items())->pluck('id')->toArray();

    expect($itemIds)->toContain($oldItem->id);
    expect($itemIds)->toContain($recentItem->id);
});

test('admin date filters are applied on in_work status', function () {
    $role = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $role->id]);

    // Create two items with status 'in_work' (4) and different created_at dates
    $oldItem = \App\Models\MarketplaceOrderItem::factory()->create([
        'status' => 4, // in_work
        'created_at' => now()->subMonth(),
    ]);

    $recentItem = \App\Models\MarketplaceOrderItem::factory()->create([
        'status' => 4, // in_work
        'created_at' => now(),
    ]);

    // Set date_start to future date (should filter out both items for admin)
    $futureDate = now()->addDay()->toDateString();

    $response = $this->actingAs($admin)
        ->get(route('marketplace_order_items.index', [
            'status' => 'in_work',
            'date_start' => $futureDate,
        ]));

    $response->assertStatus(200);

    // Both items should be hidden (date filter applied for admin)
    $items = $response->viewData('items');
    $itemIds = collect($items->items())->pluck('id')->toArray();

    expect($itemIds)->not->toContain($oldItem->id);
    expect($itemIds)->not->toContain($recentItem->id);
});
