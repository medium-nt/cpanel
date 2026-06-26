<?php

use App\Livewire\BoxOrderScanner;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSupply;
use App\Models\Sku;
use App\Models\User;
use Carbon\Carbon;
use Database\Factories\SupplyBoxFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Создаёт заказ в поставке (OZON, статус «на поставку») с одной позицией товара.
 */
function createBoxOrder(MarketplaceSupply $supply, MarketplaceItem $item): MarketplaceOrder
{
    $order = MarketplaceOrder::factory()->create([
        'supply_id' => $supply->id,
        'marketplace_id' => 1,
        'status' => 6,
        'box_id' => null,
    ]);

    MarketplaceOrderItem::factory()->create([
        'marketplace_order_id' => $order->id,
        'marketplace_item_id' => $item->id,
    ]);

    return $order;
}

it('shows the last scanned order on top and groups same items below it', function () {
    $supply = MarketplaceSupply::factory()->create(['marketplace_id' => 1]);
    $box = SupplyBoxFactory::new()->create(['marketplace_supply_id' => $supply->id]);

    $itemA = MarketplaceItem::factory()->create();
    $itemB = MarketplaceItem::factory()->create();

    Sku::factory()->create(['item_id' => $itemA->id, 'marketplace_id' => 1, 'barcode' => '111']);
    Sku::factory()->create(['item_id' => $itemB->id, 'marketplace_id' => 1, 'barcode' => '222']);

    $orderA1 = createBoxOrder($supply, $itemA);
    $orderB = createBoxOrder($supply, $itemB);
    $orderA2 = createBoxOrder($supply, $itemA);

    $testable = Livewire::actingAs(User::factory()->create())
        ->test(BoxOrderScanner::class, ['box' => $box]);

    Carbon::setTestNow('2026-06-26 10:00:00');
    $testable->set('scanCode', '111')->call('handleScan');

    Carbon::setTestNow('2026-06-26 10:00:01');
    $testable->set('scanCode', '222')->call('handleScan');

    Carbon::setTestNow('2026-06-26 10:00:02');
    $testable->set('scanCode', '111')->call('handleScan');

    $testable->assertViewHas('orders', function (Collection $orders) use ($orderA2, $orderA1, $orderB) {
        expect($orders->pluck('id')->toArray())
            ->toBe([$orderA2->id, $orderA1->id, $orderB->id]);

        return true;
    });
});

it('moves the order back to the top when removed and scanned again', function () {
    $supply = MarketplaceSupply::factory()->create(['marketplace_id' => 1]);
    $box = SupplyBoxFactory::new()->create(['marketplace_supply_id' => $supply->id]);

    $itemA = MarketplaceItem::factory()->create();
    $itemB = MarketplaceItem::factory()->create();

    Sku::factory()->create(['item_id' => $itemA->id, 'marketplace_id' => 1, 'barcode' => '111']);
    Sku::factory()->create(['item_id' => $itemB->id, 'marketplace_id' => 1, 'barcode' => '222']);

    $orderA = createBoxOrder($supply, $itemA);
    $orderB = createBoxOrder($supply, $itemB);

    $testable = Livewire::actingAs(User::factory()->create())
        ->test(BoxOrderScanner::class, ['box' => $box]);

    Carbon::setTestNow('2026-06-26 10:00:00');
    $testable->set('scanCode', '111')->call('handleScan');

    Carbon::setTestNow('2026-06-26 10:00:01');
    $testable->set('scanCode', '222')->call('handleScan');

    // Убираем orderA из короба — boxed_at должен сброситься.
    $testable->call('removeOrder', $orderA->id);

    $this->assertDatabaseHas('marketplace_orders', [
        'id' => $orderA->id,
        'box_id' => null,
        'boxed_at' => null,
    ]);

    // Повторное сканирование — orderA снова наверху (boxed_at свежее).
    Carbon::setTestNow('2026-06-26 10:00:02');
    $testable->set('scanCode', '111')->call('handleScan');

    $testable->assertViewHas('orders', function (Collection $orders) use ($orderA, $orderB) {
        expect($orders->pluck('id')->toArray())
            ->toBe([$orderA->id, $orderB->id]);

        return true;
    });
});
