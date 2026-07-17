<?php

use App\Livewire\SupplyOrderSearch;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceSupply;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('attachOrder блокирует добавление 101-го заказа при лимите 100', function () {
    $supply = MarketplaceSupply::factory()->create([
        'marketplace_id' => 2,
        'type' => 'FBS',
    ]);

    MarketplaceOrder::factory()->count(100)->create([
        'supply_id' => $supply->id,
        'marketplace_id' => 2,
    ]);

    $order101 = MarketplaceOrder::factory()->create([
        'supply_id' => null,
        'marketplace_id' => 2,
    ]);

    Livewire::test(SupplyOrderSearch::class, ['supply' => $supply])
        ->set('selectedOrderId', $order101->id)
        ->call('confirmSelectedOrder')
        ->assertSee('лимит')
        ->assertDispatched('orderError');

    expect($order101->fresh()->supply_id)->not->toBe($supply->id);
    expect($order101->fresh()->supply_id)->toBeNull();
});
