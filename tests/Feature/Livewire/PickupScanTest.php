<?php

use App\Livewire\PickupScan;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Shelf;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function createPickupItem(string $barcode, int $articleId, ?int $shelfId = null, int $status = 11, ?int $orderId = null): MarketplaceOrderItem
{
    return MarketplaceOrderItem::factory()->create([
        'marketplace_item_id' => $articleId,
        'marketplace_order_id' => $orderId ?? MarketplaceOrder::factory()->create()->id,
        'shelf_id' => $shelfId,
        'storage_barcode' => $barcode,
        'status' => $status,
    ]);
}

it('adds item and dispatches scanSuccess when article has active pickup order', function () {
    $article = MarketplaceItem::factory()->create(['title' => 'Бамбук 200х220']);
    $shelf = Shelf::create(['title' => 'A-5']);
    $order = MarketplaceOrder::factory()->create(['status' => 13]);
    createPickupItem('111111111', $article->id, $shelf->id, 11, $order->id);

    $testable = Livewire::test(PickupScan::class)
        ->set('scanCode', '111111111')
        ->call('handleScan')
        ->assertDispatched('scanSuccess');

    $scanned = $testable->get('scanned');

    expect($scanned)->toHaveCount(1)
        ->and($scanned[0]['article_title'])->toBe('Бамбук 200х220')
        ->and($scanned[0]['shelf'])->toBe('A-5')
        ->and($testable->get('statusMessage'))->toContain('1/1');
});

it('rejects extra item as «лишний» when scanned reaches needed count', function () {
    $article = MarketplaceItem::factory()->create();
    $order1 = MarketplaceOrder::factory()->create(['status' => 13]);
    $order2 = MarketplaceOrder::factory()->create(['status' => 13]);
    createPickupItem('100000001', $article->id, null, 11, $order1->id);
    createPickupItem('100000002', $article->id, null, 11, $order2->id);
    createPickupItem('100000003', $article->id, null, 11, MarketplaceOrder::factory()->create(['status' => 0])->id);

    $testable = Livewire::test(PickupScan::class);

    $testable->set('scanCode', '100000001')->call('handleScan')->assertDispatched('scanSuccess');
    $testable->set('scanCode', '100000002')->call('handleScan')->assertDispatched('scanSuccess');

    $testable->set('scanCode', '100000003')->call('handleScan')->assertDispatched('scanError');

    expect($testable->get('scanned'))->toHaveCount(2)
        ->and($testable->get('statusMessage'))->toContain('Лишний');
});

it('rejects item when no active pickup orders for the article', function () {
    $article = MarketplaceItem::factory()->create();
    createPickupItem('200000001', $article->id, null, 11, MarketplaceOrder::factory()->create(['status' => 0])->id);

    $testable = Livewire::test(PickupScan::class)
        ->set('scanCode', '200000001')
        ->call('handleScan')
        ->assertDispatched('scanError');

    expect($testable->get('scanned'))->toBeEmpty()
        ->and($testable->get('statusMessage'))->toContain('Нет активных заказов');
});

it('does not find item with status not in [11, 13]', function () {
    $article = MarketplaceItem::factory()->create();
    $order = MarketplaceOrder::factory()->create(['status' => 13]);
    createPickupItem('300000001', $article->id, null, 3, $order->id);

    Livewire::test(PickupScan::class)
        ->set('scanCode', '300000001')
        ->call('handleScan')
        ->assertDispatched('scanError')
        ->assertSet('scanned', []);
});

it('rejects duplicate scan of the same item', function () {
    $article = MarketplaceItem::factory()->create();
    $order = MarketplaceOrder::factory()->create(['status' => 13]);
    createPickupItem('400000001', $article->id, null, 11, $order->id);

    $testable = Livewire::test(PickupScan::class);

    $testable->set('scanCode', '400000001')->call('handleScan')->assertDispatched('scanSuccess');
    $testable->set('scanCode', '400000001')->call('handleScan')->assertDispatched('scanError');

    expect($testable->get('scanned'))->toHaveCount(1)
        ->and($testable->get('statusMessage'))->toContain('Уже отсканирован');
});

it('reports error for unknown barcode', function () {
    Livewire::test(PickupScan::class)
        ->set('scanCode', '999999999')
        ->call('handleScan')
        ->assertDispatched('scanError')
        ->assertSet('scanned', []);
});

it('removes item from scanned via removeScanned', function () {
    $article = MarketplaceItem::factory()->create();
    $order = MarketplaceOrder::factory()->create(['status' => 13]);
    $orderItem = createPickupItem('500000001', $article->id, null, 11, $order->id);

    $testable = Livewire::test(PickupScan::class);
    $testable->set('scanCode', '500000001')->call('handleScan');

    $testable->call('removeScanned', $orderItem->id);

    expect($testable->get('scanned'))->toBeEmpty();
});

it('clears all scanned items via clearAll', function () {
    $article = MarketplaceItem::factory()->create();
    $order = MarketplaceOrder::factory()->create(['status' => 13]);
    createPickupItem('600000001', $article->id, null, 11, $order->id);

    $testable = Livewire::test(PickupScan::class);
    $testable->set('scanCode', '600000001')->call('handleScan');

    expect($testable->get('scanned'))->toHaveCount(1);

    $testable->call('clearAll');

    expect($testable->get('scanned'))->toBeEmpty()
        ->and($testable->get('statusMessage'))->toBe('');
});
