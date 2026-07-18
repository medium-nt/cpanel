<?php

use App\Livewire\ShelfChange;
use App\Models\MarketplaceItem;
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

function createItemForShelfChange(string $barcode, int $status = 11, ?int $shelfId = null): MarketplaceOrderItem
{
    $article = MarketplaceItem::factory()->create();

    return MarketplaceOrderItem::factory()->create([
        'storage_barcode' => $barcode,
        'status' => $status,
        'shelf_id' => $shelfId,
        'marketplace_item_id' => $article->id,
    ]);
}

it('добавляет товар при сканировании валидного штрихкода со статусом 11', function () {
    $shelf = Shelf::create(['title' => 'A-1']);
    createItemForShelfChange('111111', 11);

    $testable = Livewire::test(ShelfChange::class)
        ->set('selectedShelfId', $shelf->id)
        ->set('scanCode', '111111')
        ->call('handleScan');

    expect($testable->get('scannedItems'))->toHaveCount(1)
        ->and($testable->get('statusType'))->toBe('ok');
});

it('отклоняет неизвестный штрихкод', function () {
    Livewire::test(ShelfChange::class)
        ->set('scanCode', 'UNKNOWN')
        ->call('handleScan')
        ->assertSet('statusType', 'error');
});

it('отклоняет товар с неверным статусом (не 11)', function () {
    createItemForShelfChange('222222', 12);

    $testable = Livewire::test(ShelfChange::class)
        ->set('scanCode', '222222')
        ->call('handleScan');

    expect($testable->get('statusType'))->toBe('error')
        ->and($testable->get('statusMessage'))->toContain('не находится на хранении');
});

it('отклоняет дубликат при повторном сканировании', function () {
    createItemForShelfChange('333333', 11);

    $testable = Livewire::test(ShelfChange::class);

    $testable->set('scanCode', '333333')->call('handleScan');
    $testable->set('scanCode', '333333')->call('handleScan');

    expect($testable->get('scannedItems'))->toHaveCount(1)
        ->and($testable->get('statusMessage'))->toContain('уже добавлен')
        ->and($testable->get('statusType'))->toBe('warn');
});

it('добавляет товар без выбранной полки (но saveChanges требует полку)', function () {
    createItemForShelfChange('444444', 11);

    $testable = Livewire::test(ShelfChange::class)
        ->set('selectedShelfId', null)
        ->set('scanCode', '444444')
        ->call('handleScan');

    expect($testable->get('scannedItems'))->toHaveCount(1);
});

it('отклоняет saveChanges когда полка не выбрана', function () {
    createItemForShelfChange('555555', 11);

    $testable = Livewire::test(ShelfChange::class)
        ->set('scanCode', '555555')
        ->call('handleScan')
        ->call('saveChanges');

    expect($testable->get('statusMessage'))->toContain('Полка не выбрана');
});

it('перемещает товары на выбранную полку через saveChanges', function () {
    $shelf = Shelf::create(['title' => 'A-5']);
    $item = createItemForShelfChange('666666', 11, null);

    Livewire::test(ShelfChange::class)
        ->set('selectedShelfId', $shelf->id)
        ->set('scanCode', '666666')
        ->call('handleScan')
        ->call('saveChanges');

    expect($item->fresh()->shelf_id)->toBe($shelf->id)
        ->and($item->fresh()->status)->toBe(11);
});

it('перемещает только товары со статусом 11', function () {
    $shelf = Shelf::create(['title' => 'B-1']);
    $item11 = createItemForShelfChange('777777', 11);
    $item12 = createItemForShelfChange('888888', 12);

    $testable = Livewire::test(ShelfChange::class)
        ->set('selectedShelfId', $shelf->id);

    $testable->set('scanCode', '777777')->call('handleScan');
    $testable->set('scanCode', '888888')->call('handleScan');
    $testable->call('saveChanges');

    expect($item11->fresh()->shelf_id)->toBe($shelf->id)
        ->and($item12->fresh()->shelf_id)->not->toBe($shelf->id);
});

it('удаляет товар из списка через removeFromList', function () {
    createItemForShelfChange('999999', 11);

    $testable = Livewire::test(ShelfChange::class);
    $testable->set('scanCode', '999999')->call('handleScan');

    $itemId = array_key_first($testable->get('scannedItems'));

    $testable->call('removeFromList', $itemId);

    expect($testable->get('scannedItems'))->toBeEmpty()
        ->and($testable->get('statusMessage'))->toContain('удален из списка');
});

it('отклоняет removeFromList для несуществующего товара', function () {
    Livewire::test(ShelfChange::class)
        ->call('removeFromList', 99999)
        ->assertSet('statusType', 'error');
});

it('отклоняет saveChanges когда список пуст', function () {
    Livewire::test(ShelfChange::class)
        ->call('saveChanges')
        ->assertSet('statusMessage', 'Список товаров пуст. Нечего сохранять.');
});

it('сбрасывает scannedItems после успешного saveChanges', function () {
    $shelf = Shelf::create(['title' => 'C-1']);
    createItemForShelfChange('101010', 11);

    $testable = Livewire::test(ShelfChange::class)
        ->set('selectedShelfId', $shelf->id)
        ->set('scanCode', '101010')
        ->call('handleScan')
        ->call('saveChanges');

    expect($testable->get('scannedItems'))->toBeEmpty();
});

it('обновляет сообщение при выборе полки', function () {
    Livewire::test(ShelfChange::class)
        ->set('selectedShelfId', 1)
        ->assertSet('statusType', 'ok')
        ->assertSet('statusMessage', 'Полка выбрана');
});

it('обновляет сообщение на warn когда полка снята', function () {
    Livewire::test(ShelfChange::class)
        ->set('selectedShelfId', 1)
        ->set('selectedShelfId', null)
        ->assertSet('statusType', 'warn')
        ->assertSet('statusMessage', 'Полка не выбрана');
});

it('игнорирует пустой код сканирования', function () {
    Livewire::test(ShelfChange::class)
        ->set('scanCode', '')
        ->call('handleScan')
        ->assertSet('scannedItems', [])
        ->assertSet('scanCode', '');
});
