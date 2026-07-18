<?php

use App\Livewire\StatusChangeScan;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrderItem;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'storekeeper']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function createItemForStatusChange(string $barcode, int $status): MarketplaceOrderItem
{
    $article = MarketplaceItem::factory()->create();

    return MarketplaceOrderItem::factory()->create([
        'storage_barcode' => $barcode,
        'status' => $status,
        'marketplace_item_id' => $article->id,
    ]);
}

it('добавляет товар при сканировании валидного штрихкода', function () {
    createItemForStatusChange('111111', 12);

    Livewire::test(StatusChangeScan::class)
        ->set('fromStatus', 12)
        ->set('toStatus', 13)
        ->set('scanCode', '111111')
        ->call('handleScan')
        ->assertDispatched('scanSuccess');
});

it('отклоняет товар с неверным исходным статусом', function () {
    createItemForStatusChange('222222', 11);

    Livewire::test(StatusChangeScan::class)
        ->set('fromStatus', 12)
        ->set('toStatus', 13)
        ->set('scanCode', '222222')
        ->call('handleScan')
        ->assertDispatched('scanError')
        ->assertSet('scannedItems', []);
});

it('отклоняет неизвестный штрихкод', function () {
    Livewire::test(StatusChangeScan::class)
        ->set('fromStatus', 12)
        ->set('toStatus', 13)
        ->set('scanCode', 'UNKNOWN')
        ->call('handleScan')
        ->assertDispatched('scanError');
});

it('меняет статус товаров без полки для сценария не 18->11', function () {
    $item = createItemForStatusChange('777777', 12);

    Livewire::test(StatusChangeScan::class)
        ->set('fromStatus', 12)
        ->set('toStatus', 11)
        ->set('scanCode', '777777')
        ->call('handleScan')
        ->call('complete');

    expect($item->fresh()->status)->toBe(11);
});

it('не загружает полки для сценария не 18->11', function () {
    $testable = Livewire::test(StatusChangeScan::class)
        ->set('fromStatus', 12)
        ->set('toStatus', 13);

    expect($testable->get('shelves'))->toBeNull();
});
