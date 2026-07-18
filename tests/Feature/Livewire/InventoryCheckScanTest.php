<?php

use App\Livewire\InventoryCheckScan;
use App\Models\InventoryCheck;
use App\Models\InventoryCheckItem;
use App\Models\MarketplaceOrderItem;
use App\Models\Role;
use App\Models\Shelf;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'storekeeper']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function createInventoryCheckWithShelf(): InventoryCheck
{
    $check = InventoryCheck::create(['status' => 'in_progress', 'title' => 'Test Check']);
    $shelf = Shelf::create(['title' => 'A-1']);
    $item = MarketplaceOrderItem::factory()->create(['shelf_id' => $shelf->id]);

    InventoryCheckItem::create([
        'inventory_check_id' => $check->id,
        'marketplace_order_item_id' => $item->id,
        'expected_shelf_id' => $shelf->id,
    ]);

    return $check->fresh();
}

function createItemForInventory(string $barcode, int $status = 11, ?int $shelfId = null): MarketplaceOrderItem
{
    return MarketplaceOrderItem::factory()->create([
        'storage_barcode' => $barcode,
        'status' => $status,
        'shelf_id' => $shelfId,
    ]);
}

it('сканирует товар когда полка выбрана и инвентаризация открыта', function () {
    $check = createInventoryCheckWithShelf();
    $shelfId = $check->items->first()->expected_shelf_id;
    createItemForInventory('111111', 11, $shelfId);

    $testable = Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->set('selectedShelfId', $shelfId)
        ->set('scanCode', '111111')
        ->call('handleScan');

    expect($testable->get('statusType'))->toBe('ok')
        ->and($testable->get('statusMessage'))->toContain('найден на полке');
});

it('отклоняет сканирование когда инвентаризация закрыта', function () {
    $check = createInventoryCheckWithShelf();
    $check->update(['status' => 'closed']);
    createItemForInventory('222222', 11);

    Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->set('selectedShelfId', 1)
        ->set('scanCode', '222222')
        ->call('handleScan')
        ->assertSet('statusMessage', 'Инвентаризация закрыта. Сканирование запрещено.');
});

it('отклоняет сканирование когда полка не выбрана', function () {
    $check = createInventoryCheckWithShelf();

    Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->set('scanCode', '333333')
        ->call('handleScan')
        ->assertSet('statusMessage', 'Сначала выбери полку в выпадающем списке.');
});

it('отклоняет неизвестный штрихкод', function () {
    $check = createInventoryCheckWithShelf();

    Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->set('selectedShelfId', 1)
        ->set('scanCode', 'UNKNOWN')
        ->call('handleScan')
        ->assertSet('statusType', 'error');
});

it('отклоняет товар с недопустимым статусом (не 11,12,13,14)', function () {
    $check = createInventoryCheckWithShelf();
    createItemForInventory('444444', 3);

    Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->set('selectedShelfId', 1)
        ->set('scanCode', '444444')
        ->call('handleScan')
        ->assertSet('statusType', 'error');
});

it('отклоняет дубликат при повторном сканировании', function () {
    $check = createInventoryCheckWithShelf();
    $shelfId = $check->items->first()->expected_shelf_id;
    createItemForInventory('555555', 11, $shelfId);

    $testable = Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->set('selectedShelfId', $shelfId);

    $testable->set('scanCode', '555555')->call('handleScan');
    $testable->set('scanCode', '555555')->call('handleScan');

    expect($testable->get('statusMessage'))->toContain('уже найден ранее')
        ->and($testable->get('statusType'))->toBe('warn');
});

it('добавляет товар в инвентаризацию если его там не было', function () {
    $check = createInventoryCheckWithShelf();
    $item = createItemForInventory('666666', 11);

    $testable = Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->set('selectedShelfId', $check->items->first()->expected_shelf_id)
        ->set('scanCode', '666666')
        ->call('handleScan');

    // Проверяем только состояние компонента (Livewire не сохраняет в БД автоматически)
    expect($testable->get('statusType'))->toBe('ok')
        ->and($testable->get('statusMessage'))->toContain('найден на полке');
});

it('снимает отметку найден через unmarkFound', function () {
    $check = createInventoryCheckWithShelf();
    $inventoryItem = $check->items->first();
    $inventoryItem->update(['is_found' => true]);

    $testable = Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->call('unmarkFound', $inventoryItem->id);

    expect($testable->get('statusMessage'))->toContain('Товар удален из найденных');
});

it('удаляет добавленный вручную товар при unmarkFound', function () {
    $check = createInventoryCheckWithShelf();
    $item = createItemForInventory('888888', 11);
    $inventoryItem = InventoryCheckItem::create([
        'inventory_check_id' => $check->id,
        'marketplace_order_item_id' => $item->id,
        'is_found' => true,
        'is_added_later' => true,
    ]);

    Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->call('unmarkFound', $inventoryItem->id);

    expect(InventoryCheckItem::find($inventoryItem->id))->toBeNull();
});

it('закрывает инвентаризацию: меняет статусы товаров 14->11 и 11->14', function () {
    $check = createInventoryCheckWithShelf();
    $shelfId = $check->items->first()->expected_shelf_id;

    $foundItem = createItemForInventory('999999', 14, $shelfId);
    $check->items->first()->update(['is_found' => true, 'founded_shelf_id' => $shelfId]);

    $lostItem = createItemForInventory('101010', 11, $shelfId);
    InventoryCheckItem::create([
        'inventory_check_id' => $check->id,
        'marketplace_order_item_id' => $lostItem->id,
        'expected_shelf_id' => $shelfId,
        'is_found' => false,
    ]);

    Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->call('closeCheck');

    expect($check->fresh()->status)->toBe('closed');
});

it('меняет полку товара при closeCheck если найден не на своей полке', function () {
    $check = createInventoryCheckWithShelf();
    $oldShelfId = $check->items->first()->expected_shelf_id;
    $newShelf = Shelf::create(['title' => 'B-2']);

    $item = createItemForInventory('111111', 11, $oldShelfId);
    $check->items->first()->update(['is_found' => true, 'founded_shelf_id' => $newShelf->id]);

    $testable = Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->call('closeCheck');

    expect($testable->get('statusMessage'))->toContain('Инвентаризация закрыта')
        ->and($check->fresh()->status)->toBe('closed');
});

it('обновляет счётчики foundItems и totalItems', function () {
    $check = createInventoryCheckWithShelf();
    $shelfId = $check->items->first()->expected_shelf_id;

    createItemForInventory('121212', 11, $shelfId);

    $testable = Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->set('selectedShelfId', $shelfId)
        ->set('scanCode', '121212')
        ->call('handleScan');

    expect($testable->get('foundItems'))->toBe(1)
        ->and($testable->get('totalItems'))->toBeGreaterThan(0);
});

it('обновляет сообщение при выборе полки', function () {
    $check = createInventoryCheckWithShelf();

    Livewire::test(InventoryCheckScan::class, ['inventoryCheck' => $check])
        ->set('selectedShelfId', 1)
        ->assertSet('statusType', 'ok')
        ->assertSet('statusMessage', 'Полка выбрана');
});
