<?php

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceSupply;
use App\Models\Role;
use App\Models\SupplyBox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
});

test('renders ozon fbo view with dropdown when supply is new', function () {
    $supply = MarketplaceSupply::factory()->create([
        'type' => 'FBO',
        'marketplace_id' => 1,
        'status' => 0,
        'supply_id' => null,
    ]);

    $response = $this->actingAs($this->admin)->get(route('marketplace_supplies.show', $supply));

    $response->assertOk();
    $response->assertViewIs('marketplace_supply.show-ozon-fbo');
    $response->assertViewHasAll(['supply', 'supplyOrders', 'ozonSupplyOrders']);
    expect($response->viewData('ozonSupplyOrders'))->toBeArray();
});

test('renders ozon fbo view without dropdown when supply has data', function () {
    $supply = MarketplaceSupply::factory()->create([
        'type' => 'FBO',
        'marketplace_id' => 1,
        'status' => 13,
        'supply_id' => '12345',
    ]);

    $response = $this->actingAs($this->admin)->get(route('marketplace_supplies.show', $supply));

    $response->assertOk();
    $response->assertViewIs('marketplace_supply.show-ozon-fbo');
    $response->assertViewHas('ozonSupplyOrders', []);
});

test('renders wb fbo view with dropdown when status=0', function () {
    $supply = MarketplaceSupply::factory()->create([
        'type' => 'FBO',
        'marketplace_id' => 2,
        'status' => 0,
        'supply_id' => null,
    ]);

    Http::fake(['*' => Http::response([])]);

    $response = $this->actingAs($this->admin)->get(route('marketplace_supplies.show', $supply));

    $response->assertOk();
    $response->assertViewIs('marketplace_supply.show-wb-fbo');
    $response->assertViewHasAll(['wbSupplies', 'canExportExcel']);
    expect($response->viewData('canExportExcel'))->toBeFalse();
});

test('renders wb fbo view with canExportExcel=true when status=4', function () {
    $supply = MarketplaceSupply::factory()->create([
        'type' => 'FBO',
        'marketplace_id' => 2,
        'status' => 4,
    ]);

    $response = $this->actingAs($this->admin)->get(route('marketplace_supplies.show', $supply));

    $response->assertOk();
    $response->assertViewIs('marketplace_supply.show-wb-fbo');
    expect($response->viewData('canExportExcel'))->toBeTrue();
});

test('renders fallback view for non-FBO supply', function () {
    $supply = MarketplaceSupply::factory()->create([
        'type' => 'FBS',
        'marketplace_id' => 1,
    ]);

    $response = $this->actingAs($this->admin)->get(route('marketplace_supplies.show', $supply));

    $response->assertOk();
    $response->assertViewIs('marketplace_supply.show');
    $response->assertViewHasAll(['hasShippedOrders', 'supply_orders']);
});

test('calculates hasNewOrders flag based on orders with status=0', function () {
    $supply = MarketplaceSupply::factory()->create([
        'type' => 'FBO',
        'marketplace_id' => 1,
        'status' => 13,
    ]);

    MarketplaceOrder::factory()->create(['supply_id' => $supply->id, 'status' => 0]);
    $box = SupplyBox::create(['marketplace_supply_id' => $supply->id, 'number' => 'BOX-1']);
    MarketplaceOrder::factory()->create(['supply_id' => $supply->id, 'status' => 4, 'box_id' => $box->id]);

    $response = $this->actingAs($this->admin)->get(route('marketplace_supplies.show', $supply));

    expect($response->viewData('hasNewOrders'))->toBeTrue();
    expect($response->viewData('hasNotReadyOrders'))->toBeFalse();
    expect($response->viewData('hasOnSupplyOrders'))->toBeFalse();
});

test('calculates hasNotReadyOrders flag based on status=4 orders without box', function () {
    $supply = MarketplaceSupply::factory()->create([
        'type' => 'FBO',
        'marketplace_id' => 1,
        'status' => 13,
    ]);

    MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'status' => 4,
        'box_id' => null,
    ]);

    $response = $this->actingAs($this->admin)->get(route('marketplace_supplies.show', $supply));

    expect($response->viewData('hasNotReadyOrders'))->toBeTrue();
});

test('calculates hasOnSupplyOrders flag based on status=6 orders without box', function () {
    $supply = MarketplaceSupply::factory()->create([
        'type' => 'FBO',
        'marketplace_id' => 1,
        'status' => 13,
    ]);

    MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'status' => 6,
        'box_id' => null,
    ]);

    $response = $this->actingAs($this->admin)->get(route('marketplace_supplies.show', $supply));

    expect($response->viewData('hasOnSupplyOrders'))->toBeTrue();
});

test('update fbo preserves boxes_count when field is not submitted (disabled)', function () {
    $supply = MarketplaceSupply::factory()->create([
        'gazelka_shipment_date' => today()->subDay(),
        'boxes_count' => 5,
        'gazelka_shipment_id' => 'OLD-1',
    ]);

    expect($supply->canEditBoxesCount())->toBeFalse();

    $response = $this->actingAs($this->admin)->put(
        route('marketplace_supplies.update_fbo', $supply),
        ['gazelka_shipment_id' => 'NEW-123'],
    );

    $response->assertRedirect(route('marketplace_supplies.show', $supply));
    $response->assertSessionHas('success');

    $supply->refresh();
    expect($supply->boxes_count)->toBe(5)
        ->and($supply->gazelka_shipment_id)->toBe('NEW-123');
});

test('update fbo blocks boxes_count when shipment date passed even if field is submitted', function () {
    $supply = MarketplaceSupply::factory()->create([
        'gazelka_shipment_date' => today()->subDay(),
        'boxes_count' => 5,
    ]);

    $response = $this->actingAs($this->admin)->put(
        route('marketplace_supplies.update_fbo', $supply),
        ['boxes_count' => 99],
    );

    $response->assertSessionHas('error');
    expect($supply->fresh()->boxes_count)->toBe(5);
});

test('update fbo updates boxes_count when editable', function () {
    $supply = MarketplaceSupply::factory()->create([
        'gazelka_shipment_date' => null,
        'boxes_count' => 5,
    ]);

    expect($supply->canEditBoxesCount())->toBeTrue();

    $response = $this->actingAs($this->admin)->put(
        route('marketplace_supplies.update_fbo', $supply),
        ['boxes_count' => 7],
    );

    $response->assertRedirect(route('marketplace_supplies.show', $supply));
    expect($supply->fresh()->boxes_count)->toBe(7);
});

test('update fbo updates supply_date', function () {
    $supply = MarketplaceSupply::factory()->create([
        'gazelka_shipment_date' => today()->addDays(2),
        'supply_date' => today()->addDays(10),
    ]);

    $newSupplyDate = today()->addDays(12)->format('Y-m-d');

    $response = $this->actingAs($this->admin)->put(
        route('marketplace_supplies.update_fbo', $supply),
        [
            'gazelka_shipment_date' => today()->addDays(2)->format('Y-m-d'),
            'supply_date' => $newSupplyDate,
        ],
    );

    $response->assertRedirect(route('marketplace_supplies.show', $supply));
    expect($supply->fresh()->supply_date->format('Y-m-d'))->toBe($newSupplyDate);
});

test('update fbo blocks supply_date not later than gazelka shipment date', function () {
    $supply = MarketplaceSupply::factory()->create([
        'gazelka_shipment_date' => today()->addDays(5),
        'supply_date' => today()->addDays(10),
    ]);

    $response = $this->actingAs($this->admin)->put(
        route('marketplace_supplies.update_fbo', $supply),
        ['supply_date' => today()->addDays(5)->format('Y-m-d')],
    );

    $response->assertSessionHas('error');
    expect($supply->fresh()->supply_date->format('Y-m-d'))->toBe(today()->addDays(10)->format('Y-m-d'));
});

test('update fbo clears supply_date', function () {
    $supply = MarketplaceSupply::factory()->create([
        'gazelka_shipment_date' => null,
        'supply_date' => today()->addDays(10),
    ]);

    $response = $this->actingAs($this->admin)->put(
        route('marketplace_supplies.update_fbo', $supply),
        ['supply_date' => null],
    );

    $response->assertRedirect(route('marketplace_supplies.show', $supply));
    expect($supply->fresh()->supply_date)->toBeNull();
});
