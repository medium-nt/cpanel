<?php

use App\Models\MarketplaceSupply;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
    ]);
});

it('reverts a shipped supply back to status 4 and preserves completed_at', function () {
    $completedAt = now();
    $supply = MarketplaceSupply::factory()->create([
        'status' => 3,
        'completed_at' => $completedAt,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('marketplace_supplies.unmark_shipped', $supply));

    $response->assertRedirect(route('marketplace_supplies.show', $supply));
    $response->assertSessionHas('success', 'Отгрузка поставки отменена.');

    $fresh = $supply->fresh();
    expect($fresh->status)->toBe(4)
        ->and($fresh->completed_at?->getTimestamp())->toBe($completedAt->getTimestamp());
});

it('forbids non-admin roles from reverting shipment', function () {
    $supply = MarketplaceSupply::factory()->create(['status' => 3]);

    foreach (['storekeeper', 'manager', 'seamstress'] as $roleName) {
        $user = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
        ]);

        $this->actingAs($user)
            ->get(route('marketplace_supplies.unmark_shipped', $supply))
            ->assertForbidden();
    }

    expect($supply->fresh()->status)->toBe(3);
});

it('refuses to revert a supply that is not shipped yet', function () {
    $supply = MarketplaceSupply::factory()->create(['status' => 4]);

    $response = $this->actingAs($this->admin)
        ->from(route('marketplace_supplies.show', $supply))
        ->get(route('marketplace_supplies.unmark_shipped', $supply));

    $response->assertRedirect(route('marketplace_supplies.show', $supply));
    $response->assertSessionHas('error', 'Поставка не отгружена.');

    expect($supply->fresh()->status)->toBe(4);
});
