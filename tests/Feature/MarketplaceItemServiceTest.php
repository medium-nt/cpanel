<?php

use App\Models\MarketplaceItem;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\Role;
use App\Models\Sku;
use App\Models\User;
use App\Services\MarketplaceItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('getFilteredItems returns query builder with title filter', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    MarketplaceItem::factory()->create(['title' => 'Test Item 1']);
    MarketplaceItem::factory()->create(['title' => 'Test Item 2']);

    $request = new \Illuminate\Http\Request;
    $request->merge(['title' => 'Test Item 1']);

    $query = MarketplaceItemService::getFilteredItems($request);

    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
    $results = $query->get();
    expect($results->count())->toBe(1);
    expect($results->first()->title)->toBe('Test Item 1');
});

test('getFilteredItems returns query builder with width filter', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    MarketplaceItem::factory()->create(['width' => 100]);
    MarketplaceItem::factory()->create(['width' => 200]);

    $request = new \Illuminate\Http\Request;
    $request->merge(['width' => 100]);

    $query = MarketplaceItemService::getFilteredItems($request);

    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
    $results = $query->get();
    expect($results->count())->toBe(1);
    expect($results->first()->width)->toBe(100);
});

test('getFilteredItems returns base query builder without filters', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    // Clean up existing records and create test ones
    MarketplaceItem::query()->delete();
    MarketplaceItem::factory()->create();
    MarketplaceItem::factory()->create();

    $request = new \Illuminate\Http\Request;

    $query = MarketplaceItemService::getFilteredItems($request);

    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
    $results = $query->get();
    expect($results->count())->toBe(2);
});

test('saveSkus creates and updates SKUs for marketplaces', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $marketplaceItem = MarketplaceItem::factory()->create();

    $request = new \Illuminate\Http\Request;
    $request->merge([
        'ozon_sku' => 'OZ12345',
        'wb_sku' => 'WB67890',
    ]);

    MarketplaceItemService::saveSkus($marketplaceItem, $request);

    $this->assertDatabaseHas('skus', [
        'item_id' => $marketplaceItem->id,
        'marketplace_id' => 1,
        'sku' => 'OZ12345',
    ]);

    $this->assertDatabaseHas('skus', [
        'item_id' => $marketplaceItem->id,
        'marketplace_id' => 2,
        'sku' => 'WB67890',
    ]);
});

test('saveSkus updates existing SKUs', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $marketplaceItem = MarketplaceItem::factory()->create();

    // Create existing SKU
    Sku::create([
        'item_id' => $marketplaceItem->id,
        'marketplace_id' => 1,
        'sku' => 'OLD123',
    ]);

    $request = new \Illuminate\Http\Request;
    $request->merge([
        'ozon_sku' => 'OZ12345',
        'wb_sku' => 'WB67890',
    ]);

    MarketplaceItemService::saveSkus($marketplaceItem, $request);

    // Check that SKU was updated
    $this->assertDatabaseHas('skus', [
        'item_id' => $marketplaceItem->id,
        'marketplace_id' => 1,
        'sku' => 'OZ12345',
    ]);

    // Check that old SKU was updated, not duplicated
    $skuCount = Sku::where('item_id', $marketplaceItem->id)
        ->where('marketplace_id', 1)
        ->count();
    expect($skuCount)->toBe(1);
});

test('saveSkus does nothing when SKU fields are empty', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $marketplaceItem = MarketplaceItem::factory()->create();

    $request = new \Illuminate\Http\Request;
    $request->merge([
        'ozon_sku' => '',
        'wb_sku' => '',
    ]);

    MarketplaceItemService::saveSkus($marketplaceItem, $request);

    // Check no SKUs were created for this specific item
    $skusForItem = Sku::where('item_id', $marketplaceItem->id)->count();
    expect($skusForItem)->toBe(0);
});

test('saveMaterialsConsumption creates material consumption records', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $marketplaceItem = MarketplaceItem::factory()->create();
    $material1 = Material::factory()->create();
    $material2 = Material::factory()->create();

    $request = new \Illuminate\Http\Request;
    $request->merge([
        'material_id' => [$material1->id, $material2->id],
        'quantity' => [5, 10],
    ]);

    MarketplaceItemService::saveMaterialsConsumption($marketplaceItem, $request);

    $this->assertDatabaseHas('material_consumptions', [
        'item_id' => $marketplaceItem->id,
        'material_id' => $material1->id,
        'quantity' => 5,
    ]);

    $this->assertDatabaseHas('material_consumptions', [
        'item_id' => $marketplaceItem->id,
        'material_id' => $material2->id,
        'quantity' => 10,
    ]);
});

test('saveMaterialsConsumption skips zero quantities', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $marketplaceItem = MarketplaceItem::factory()->create();
    $material = Material::factory()->create();

    $request = new \Illuminate\Http\Request;
    $request->merge([
        'material_id' => [$material->id],
        'quantity' => [0],
    ]);

    MarketplaceItemService::saveMaterialsConsumption($marketplaceItem, $request);

    // No records should be created for zero quantity
    $consumptions = MaterialConsumption::where('item_id', $marketplaceItem->id)->count();
    expect($consumptions)->toBe(0);
});

test('saveMaterialsConsumption does nothing when material_id is null', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $marketplaceItem = MarketplaceItem::factory()->create();

    $request = new \Illuminate\Http\Request;
    $request->merge([
        'material_id' => null,
        'quantity' => [5],
    ]);

    MarketplaceItemService::saveMaterialsConsumption($marketplaceItem, $request);

    // No records should be created
    $consumptions = MaterialConsumption::where('item_id', $marketplaceItem->id)->count();
    expect($consumptions)->toBe(0);
});

test('getAllTitleMaterials returns distinct titles', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    // Clean up and create test data
    MarketplaceItem::query()->delete();
    MarketplaceItem::factory()->create(['title' => 'Title 1']);
    MarketplaceItem::factory()->create(['title' => 'Title 2']);
    MarketplaceItem::factory()->create(['title' => 'Title 1']); // Duplicate

    $titles = MarketplaceItemService::getAllTitleMaterials();

    expect($titles)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($titles->count())->toBe(2);
    expect($titles->pluck('title')->toArray())->toEqualCanonicalizing(['Title 1', 'Title 2']);
});

test('getAllWidthMaterials returns distinct widths', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    // Clean up and create test data
    MarketplaceItem::query()->delete();
    MarketplaceItem::factory()->create(['width' => 100]);
    MarketplaceItem::factory()->create(['width' => 200]);
    MarketplaceItem::factory()->create(['width' => 100]); // Duplicate

    $widths = MarketplaceItemService::getAllWidthMaterials();

    expect($widths)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($widths->count())->toBe(2);
    expect($widths->pluck('width')->toArray())->toEqualCanonicalizing([100, 200]);
});

test('getAllHeightMaterials returns distinct heights', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    // Clean up and create test data
    MarketplaceItem::query()->delete();
    MarketplaceItem::factory()->create(['height' => 150]);
    MarketplaceItem::factory()->create(['height' => 250]);
    MarketplaceItem::factory()->create(['height' => 150]); // Duplicate

    $heights = MarketplaceItemService::getAllHeightMaterials();

    expect($heights)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($heights->count())->toBe(2);
    expect($heights->pluck('height')->toArray())->toEqualCanonicalizing([150, 250]);
});
