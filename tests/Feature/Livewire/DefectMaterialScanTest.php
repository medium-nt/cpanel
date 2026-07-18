<?php

use App\Livewire\DefectMaterialScan;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'storekeeper']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function createDefectOrder(string $code = 'DEF-1', int $status = 1): Order
{
    $order = Order::factory()->create([
        'id' => (int) str_replace('DEF-', '', $code),
        'type_movement' => 4,
        'status' => $status,
    ]);

    MovementMaterial::factory()->create([
        'order_id' => $order->id,
    ]);

    return $order;
}

it('добавляет заявку при сканировании валидного кода DEF-123', function () {
    createDefectOrder('DEF-100');

    $testable = Livewire::test(DefectMaterialScan::class)
        ->set('scanCode', 'DEF-100')
        ->call('handleScan')
        ->assertDispatched('scanSuccess');

    expect($testable->get('scannedOrderIds'))->toBe([100])
        ->and($testable->get('statusMessage'))->toContain('Добавлено')
        ->and($testable->get('statusType'))->toBe('ok');
});

it('отклоняет код неверного формата (не DEF-123)', function () {
    Livewire::test(DefectMaterialScan::class)
        ->set('scanCode', 'INVALID-100')
        ->call('handleScan')
        ->assertDispatched('scanError');

    Livewire::test(DefectMaterialScan::class)
        ->set('scanCode', '100')
        ->call('handleScan')
        ->assertDispatched('scanError');
});

it('отклоняет заявку с неверным статусом (не 1)', function () {
    createDefectOrder('DEF-200', status: 3);

    Livewire::test(DefectMaterialScan::class)
        ->set('scanCode', 'DEF-200')
        ->call('handleScan')
        ->assertDispatched('scanError')
        ->assertSet('scannedOrderIds', []);
});

it('отклоняет не существующую заявку', function () {
    Livewire::test(DefectMaterialScan::class)
        ->set('scanCode', 'DEF-999')
        ->call('handleScan')
        ->assertDispatched('scanError')
        ->assertSet('scannedOrderIds', []);
});

it('отклоняет дубликат при повторном сканировании', function () {
    createDefectOrder('DEF-300');

    $testable = Livewire::test(DefectMaterialScan::class);

    $testable->set('scanCode', 'DEF-300')->call('handleScan')->assertDispatched('scanSuccess');
    $testable->set('scanCode', 'DEF-300')->call('handleScan');

    expect($testable->get('scannedOrderIds'))->toHaveCount(1)
        ->and($testable->get('statusMessage'))->toContain('уже добавлена')
        ->and($testable->get('statusType'))->toBe('warn');
});

it('удаляет заявку из списка через removeFromList', function () {
    createDefectOrder('DEF-400');

    $testable = Livewire::test(DefectMaterialScan::class);
    $testable->set('scanCode', 'DEF-400')->call('handleScan');

    expect($testable->get('scannedOrderIds'))->toHaveCount(1);

    $testable->call('removeFromList', 400);

    expect($testable->get('scannedOrderIds'))->toBeEmpty()
        ->and($testable->get('statusMessage'))->toContain('удалена из списка');
});

it('принимает все заявки: меняет статус на 3', function () {
    createDefectOrder('DEF-501');
    createDefectOrder('DEF-502');

    Livewire::test(DefectMaterialScan::class)
        ->set('scanCode', 'DEF-501')->call('handleScan')
        ->set('scanCode', 'DEF-502')->call('handleScan')
        ->call('acceptAll');

    expect(Order::where('id', 501)->first()->status)->toBe(3)
        ->and(Order::where('id', 502)->first()->status)->toBe(3);
});

it('отклоняет acceptAll когда список пуст', function () {
    Livewire::test(DefectMaterialScan::class)
        ->call('acceptAll')
        ->assertDispatched('scanError')
        ->assertSet('scannedOrderIds', []);
});

it('игнорирует пустой код сканирования', function () {
    $testable = Livewire::test(DefectMaterialScan::class)
        ->set('scanCode', '')
        ->call('handleScan')
        ->assertSet('scannedOrderIds', [])
        ->assertSet('scanCode', '');

    $testable->set('scanCode', '   ')
        ->call('handleScan')
        ->assertSet('scannedOrderIds', []);
});

it('считает totalAvailableOrders', function () {
    createDefectOrder('DEF-600');
    createDefectOrder('DEF-601');
    Order::factory()->create(['type_movement' => 4, 'status' => 0]);

    $testable = Livewire::test(DefectMaterialScan::class);

    // Рендер триггерится автоматически при создании теста
    expect($testable->get('totalAvailableOrders'))->toBe(2);
});
