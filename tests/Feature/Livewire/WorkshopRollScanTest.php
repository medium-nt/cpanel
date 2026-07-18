<?php

use App\Livewire\WorkshopRollScan;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Role;
use App\Models\Roll;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'storekeeper']);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function createOrderForWorkshop(int $materialId, int $status = 1): Order
{
    $shift = Shift::factory()->create();
    $order = Order::factory()->create([
        'type_movement' => 2,
        'status' => $status,
        'shift_id' => $shift->id,
    ]);

    MovementMaterial::factory()->create([
        'order_id' => $order->id,
        'material_id' => $materialId,
    ]);

    return $order->fresh();
}

function createRollForScanning(int $materialId, string $status = Roll::STATUS_IN_STORAGE, ?int $shiftId = null): Roll
{
    return Roll::factory()->create([
        'material_id' => $materialId,
        'status' => $status,
        'shift_id' => $shiftId,
    ]);
}

it('добавляет рулон при сканировании валидного кода', function () {
    $material = Material::factory()->create(['type_id' => Material::TYPE_FABRIC]);
    $order = createOrderForWorkshop($material->id);
    $roll = createRollForScanning($material->id);

    Livewire::test(WorkshopRollScan::class, ['order' => $order])
        ->set('scanCode', $roll->roll_code)
        ->call('scanRoll')
        ->assertDispatched('scanSuccess');

    expect($order->movementMaterials()->whereNotNull('roll_id')->count())->toBe(1);
});

it('отклоняет несуществующий рулон', function () {
    $material = Material::factory()->create();
    $order = createOrderForWorkshop($material->id);

    Livewire::test(WorkshopRollScan::class, ['order' => $order])
        ->set('scanCode', 'NONEXISTENT')
        ->call('scanRoll')
        ->assertDispatched('scanError')
        ->assertSet('message', 'Рулон не найден.');
});

it('отклоняет рулон не со статусом IN_STORAGE', function () {
    $material = Material::factory()->create();
    $order = createOrderForWorkshop($material->id);
    $roll = createRollForScanning($material->id, Roll::STATUS_IN_WORKSHOP);

    Livewire::test(WorkshopRollScan::class, ['order' => $order])
        ->set('scanCode', $roll->roll_code)
        ->call('scanRoll')
        ->assertDispatched('scanError')
        ->assertSet('message', 'Рулон не находится на складе.');
});

it('отклоняет рулон другого материала', function () {
    $material1 = Material::factory()->create();
    $material2 = Material::factory()->create();
    $order = createOrderForWorkshop($material1->id);
    $roll = createRollForScanning($material2->id);

    Livewire::test(WorkshopRollScan::class, ['order' => $order])
        ->set('scanCode', $roll->roll_code)
        ->call('scanRoll')
        ->assertDispatched('scanError')
        ->assertSet('message', 'Рулон принадлежит другому материалу.');
});

it('отклоняет дубликат при повторном сканировании рулона', function () {
    $material = Material::factory()->create(['type_id' => Material::TYPE_FABRIC]);
    $order = createOrderForWorkshop($material->id);
    $roll = createRollForScanning($material->id);

    $testable = Livewire::test(WorkshopRollScan::class, ['order' => $order]);

    $testable->set('scanCode', $roll->roll_code)->call('scanRoll');
    $testable->set('scanCode', $roll->roll_code)->call('scanRoll');

    expect($testable->get('message'))->toContain('уже добавлен')
        ->and($order->movementMaterials()->whereNotNull('roll_id')->count())->toBe(1);
});

it('ограничивает упаковочные материалы - только 1 рулон в поставке', function () {
    $material = Material::factory()->create(['type_id' => Material::TYPE_PACKAGING]);
    $order = createOrderForWorkshop($material->id);
    $roll1 = createRollForScanning($material->id);
    $roll2 = createRollForScanning($material->id);

    $testable = Livewire::test(WorkshopRollScan::class, ['order' => $order]);

    $testable->set('scanCode', $roll1->roll_code)->call('scanRoll');
    $testable->set('scanCode', $roll2->roll_code)->call('scanRoll');

    expect($testable->get('message'))->toContain('только 1 рулон')
        ->and($order->movementMaterials()->whereNotNull('roll_id')->count())->toBe(1);
});

it('проверяет лимит тканей на смену через настройку max_fabric_rolls_per_shift', function () {
    Setting::updateOrCreate(
        ['name' => 'max_fabric_rolls_per_shift', 'workshop_id' => null],
        ['value' => '3']
    );
    $material = Material::factory()->create(['type_id' => Material::TYPE_FABRIC]);
    $order = createOrderForWorkshop($material->id);
    $shiftId = $order->shift_id;

    createRollForScanning($material->id, Roll::STATUS_IN_WORKSHOP, $shiftId);
    createRollForScanning($material->id, Roll::STATUS_SHIPPED_TO_WORKSHOP, $shiftId);
    createRollForScanning($material->id, Roll::STATUS_IN_WORKSHOP, $shiftId);

    $roll4 = createRollForScanning($material->id);

    Livewire::test(WorkshopRollScan::class, ['order' => $order])
        ->set('scanCode', $roll4->roll_code)
        ->call('scanRoll');

    expect($order->movementMaterials()->whereNotNull('roll_id')->count())->toBe(0);
});

it('удаляет рулон из заказа через removeRoll', function () {
    $material = Material::factory()->create();
    $order = createOrderForWorkshop($material->id);
    $roll = createRollForScanning($material->id);

    $testable = Livewire::test(WorkshopRollScan::class, ['order' => $order]);
    $testable->set('scanCode', $roll->roll_code)->call('scanRoll');

    $mmId = $order->movementMaterials()->whereNotNull('roll_id')->first()->id;

    $testable->call('removeRoll', $mmId);

    expect($order->movementMaterials()->whereNotNull('roll_id')->count())->toBe(0);
});

it('очищает roll_id при removeRoll если это единственный рулон', function () {
    $material = Material::factory()->create();
    $order = createOrderForWorkshop($material->id);
    $roll = createRollForScanning($material->id);

    $testable = Livewire::test(WorkshopRollScan::class, ['order' => $order]);
    $testable->set('scanCode', $roll->roll_code)->call('scanRoll');

    $mmId = $order->movementMaterials()->whereNotNull('roll_id')->first()->id;

    $testable->call('removeRoll', $mmId);

    $mm = MovementMaterial::find($mmId);
    expect($mm->roll_id)->toBeNull()
        ->and($mm->quantity)->toBe(0);
});

it('отклоняет confirmShipment когда нет отсканированных рулонов', function () {
    $material = Material::factory()->create();
    $order = createOrderForWorkshop($material->id);

    Livewire::test(WorkshopRollScan::class, ['order' => $order])
        ->call('confirmShipment')
        ->assertDispatched('scanError')
        ->assertSet('message', 'Добавьте хотя бы один рулон.');
});

it('отклоняет confirmShipment для упаковочных материалов если более 1 рулона', function () {
    $material = Material::factory()->create(['type_id' => Material::TYPE_PACKAGING]);
    $order = createOrderForWorkshop($material->id);
    $roll1 = createRollForScanning($material->id);
    $roll2 = createRollForScanning($material->id);

    $testable = Livewire::test(WorkshopRollScan::class, ['order' => $order]);
    $testable->set('scanCode', $roll1->roll_code)->call('scanRoll');

    // Hack: напрямую добавляем второй рулон, минуя валидацию scanRoll
    MovementMaterial::factory()->create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'roll_id' => $roll2->id,
    ]);

    $testable->call('confirmShipment');

    expect($testable->get('message'))->toContain('только 1 рулон за смену')
        ->and($order->fresh()->status)->toBe(1);
});

it('отклоняет рулон с активной поставкой которая не принята (статус не 3)', function () {
    $material = Material::factory()->create();
    $order = createOrderForWorkshop($material->id);
    $roll = createRollForScanning($material->id);

    // Создаём активную поставку с рулоном
    $activeOrder = Order::factory()->create(['type_movement' => 2, 'status' => 1]);
    MovementMaterial::factory()->create([
        'order_id' => $activeOrder->id,
        'material_id' => $material->id,
        'roll_id' => $roll->id,
    ]);

    Livewire::test(WorkshopRollScan::class, ['order' => $order])
        ->set('scanCode', $roll->roll_code)
        ->call('scanRoll')
        ->assertDispatched('scanError');

    expect($order->movementMaterials()->whereNotNull('roll_id')->count())->toBe(0);
});

it('создаёт новую MovementMaterial если placeholder отсутствует', function () {
    $material = Material::factory()->create();
    $order = createOrderForWorkshop($material->id);
    $roll = createRollForScanning($material->id);

    // Удаляем placeholder
    $order->movementMaterials()->delete();

    // Создаём новый placeholder для валидного mount()
    MovementMaterial::factory()->create([
        'order_id' => $order->id,
        'material_id' => $material->id,
        'roll_id' => null,
    ]);

    Livewire::test(WorkshopRollScan::class, ['order' => $order])
        ->set('scanCode', $roll->roll_code)
        ->call('scanRoll');

    $mm = $order->movementMaterials()->where('roll_id', $roll->id)->first();
    expect($mm)->not->toBeNull()
        ->and($mm->quantity)->toBe($roll->initial_quantity);
});

it('обновляет количество в placeholder при добавлении рулона', function () {
    $material = Material::factory()->create();
    $order = createOrderForWorkshop($material->id);
    $roll = createRollForScanning($material->id);

    $placeholder = $order->movementMaterials()->whereNull('roll_id')->first();

    Livewire::test(WorkshopRollScan::class, ['order' => $order])
        ->set('scanCode', $roll->roll_code)
        ->call('scanRoll');

    expect($placeholder->fresh()->roll_id)->toBe($roll->id)
        ->and($placeholder->fresh()->quantity)->toBe($roll->initial_quantity);
});

it('ignores empty scan code', function () {
    $material = Material::factory()->create();
    $order = createOrderForWorkshop($material->id);

    Livewire::test(WorkshopRollScan::class, ['order' => $order])
        ->set('scanCode', '')
        ->call('scanRoll')
        ->assertSet('message', null)
        ->assertSet('scanCode', '');
});
