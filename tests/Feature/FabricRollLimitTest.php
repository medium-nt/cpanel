<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Role;
use App\Models\Roll;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FabricRollLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $storekeeper;

    private User $seamstress;

    private Shift $shift;

    private Material $fabricMaterial;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['name' => 'is_enabled_work_shift'], ['value' => '1']);

        $storekeeperRole = Role::firstOrCreate(['name' => 'storekeeper']);
        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);

        $this->storekeeper = User::factory()->create([
            'role_id' => $storekeeperRole->id,
            'shift_is_open' => true,
        ]);
        $this->seamstress = User::factory()->create([
            'role_id' => $seamstressRole->id,
            'shift_is_open' => true,
        ]);

        $workshop = \App\Models\Workshop::create([
            'title' => 'Тестовый цех',
            'status' => \App\Models\Workshop::STATUS_ACTIVE,
        ]);

        $this->shift = Shift::create([
            'name' => 'Тестовая смена',
            'status' => Shift::STATUS_ACTIVE,
            'workshop_id' => $workshop->id,
        ]);

        $this->fabricMaterial = Material::factory()->create([
            'title' => 'Тестовая ткань',
            'type_id' => Material::TYPE_FABRIC,
        ]);
    }

    /**
     * Создать заказ на перемещение в цех (type_movement=2) с placeholder MovementMaterial.
     */
    private function createWorkshopOrder(): Order
    {
        $order = Order::factory()->create([
            'type_movement' => 2,
            'status' => 1,
            'shift_id' => $this->shift->id,
        ]);

        MovementMaterial::create([
            'order_id' => $order->id,
            'material_id' => $this->fabricMaterial->id,
            'quantity' => 0,
            'ordered_quantity' => 0,
        ]);

        return $order;
    }

    /**
     * Создать рулон ткани с заданным статусом и привязкой к смене.
     */
    private function createRoll(string $status, ?int $shiftId = null): Roll
    {
        return Roll::factory()->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => $status,
            'shift_id' => $shiftId ?? $this->shift->id,
            'initial_quantity' => 100,
        ]);
    }

    // ─────────────────────────────────────────────────────
    // WorkshopRollScan::scanRoll() — сканирование кладовщиком
    // ─────────────────────────────────────────────────────

    #[Test]
    public function scan_fabric_roll_allowed_under_limit(): void
    {
        // В цехе 5, в пути 3 = 8. Сканируем 1-й → всего 9. Должно разрешить.
        $this->createRoll(Roll::STATUS_IN_WORKSHOP);
        Roll::factory()->count(4)->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'shift_id' => $this->shift->id,
            'initial_quantity' => 100,
        ]);
        Roll::factory()->count(3)->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => Roll::STATUS_SHIPPED_TO_WORKSHOP,
            'shift_id' => $this->shift->id,
            'initial_quantity' => 100,
        ]);

        $order = $this->createWorkshopOrder();
        $rollToScan = $this->createRoll(Roll::STATUS_IN_STORAGE, null);

        Livewire::test('workshop-roll-scan', ['order' => $order])
            ->set('scanCode', $rollToScan->roll_code)
            ->call('scanRoll')
            ->assertNotSet('message', 'В цехе уже 10 рулонов этой ткани. Лимит достигнут.')
            ->assertSet('messageType', 'success');
    }

    #[Test]
    public function scan_fabric_roll_blocked_at_limit(): void
    {
        // В цехе 5, в пути 3, отсканировано 2 = 10. Сканировать нельзя.
        Roll::factory()->count(5)->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'shift_id' => $this->shift->id,
            'initial_quantity' => 100,
        ]);
        Roll::factory()->count(3)->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => Roll::STATUS_SHIPPED_TO_WORKSHOP,
            'shift_id' => $this->shift->id,
            'initial_quantity' => 100,
        ]);

        $order = $this->createWorkshopOrder();

        // 2 рулона уже отсканированы и привязаны к MovementMaterial (status остаётся IN_STORAGE)
        $scannedRolls = Roll::factory()->count(2)->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => Roll::STATUS_IN_STORAGE,
            'shift_id' => null,
            'initial_quantity' => 100,
        ]);

        foreach ($scannedRolls as $roll) {
            $order->movementMaterials()->create([
                'material_id' => $this->fabricMaterial->id,
                'roll_id' => $roll->id,
                'quantity' => $roll->initial_quantity,
                'ordered_quantity' => 0,
            ]);
        }

        $rollToScan = $this->createRoll(Roll::STATUS_IN_STORAGE, null);

        Livewire::test('workshop-roll-scan', ['order' => $order])
            ->set('scanCode', $rollToScan->roll_code)
            ->call('scanRoll')
            ->assertSet('messageType', 'error')
            ->assertSet('message', 'В цехе уже 10 рулонов этой ткани. Лимит достигнут.');
    }

    #[Test]
    public function scan_fabric_roll_does_not_count_other_shifts(): void
    {
        // Рулоны другой смены не должны учитываться
        $otherShift = Shift::create([
            'name' => 'Другая смена',
            'status' => Shift::STATUS_ACTIVE,
            'workshop_id' => $this->shift->workshop_id,
        ]);

        Roll::factory()->count(10)->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'shift_id' => $otherShift->id,
            'initial_quantity' => 100,
        ]);

        $order = $this->createWorkshopOrder();
        $rollToScan = $this->createRoll(Roll::STATUS_IN_STORAGE, null);

        // У нашей смены 0 рулонов — сканирование должно пройти
        Livewire::test('workshop-roll-scan', ['order' => $order])
            ->set('scanCode', $rollToScan->roll_code)
            ->call('scanRoll')
            ->assertSet('messageType', 'success');
    }

    #[Test]
    public function scan_fabric_roll_does_not_count_completed_rolls(): void
    {
        // Завершённые рулоны не учитываются
        Roll::factory()->count(10)->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => Roll::STATUS_COMPLETED,
            'shift_id' => $this->shift->id,
            'initial_quantity' => 100,
            'completed_at' => now(),
        ]);

        $order = $this->createWorkshopOrder();
        $rollToScan = $this->createRoll(Roll::STATUS_IN_STORAGE, null);

        Livewire::test('workshop-roll-scan', ['order' => $order])
            ->set('scanCode', $rollToScan->roll_code)
            ->call('scanRoll')
            ->assertSet('messageType', 'success');
    }

    // ─────────────────────────────────────────────────────
    // Controller::save_receive() — приём в цехе
    // ─────────────────────────────────────────────────────

    #[Test]
    public function receive_fabric_allowed_under_limit(): void
    {
        // В цехе 5, принимаем 4 = 9 — можно
        Roll::factory()->count(5)->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'shift_id' => $this->shift->id,
            'initial_quantity' => 100,
        ]);

        $order = $this->createWorkshopOrder();
        $order->update(['status' => 2]);

        // Создаём 4 рулона для приёмки
        $receivingRolls = Roll::factory()->count(4)->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => Roll::STATUS_SHIPPED_TO_WORKSHOP,
            'shift_id' => $this->shift->id,
            'initial_quantity' => 100,
        ]);

        foreach ($receivingRolls as $roll) {
            $order->movementMaterials()->create([
                'material_id' => $this->fabricMaterial->id,
                'roll_id' => $roll->id,
                'quantity' => $roll->initial_quantity,
                'ordered_quantity' => 0,
            ]);
        }

        $response = $this->actingAs($this->seamstress)
            ->put(route('movements_to_workshop.save_receive', $order));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertEquals(3, $order->fresh()->status);
    }

    #[Test]
    public function receive_fabric_blocked_over_limit(): void
    {
        // В цехе 8, принимаем 3 = 11 — нельзя
        Roll::factory()->count(8)->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'shift_id' => $this->shift->id,
            'initial_quantity' => 100,
        ]);

        $order = $this->createWorkshopOrder();
        $order->update(['status' => 2]);

        $receivingRolls = Roll::factory()->count(3)->create([
            'material_id' => $this->fabricMaterial->id,
            'status' => Roll::STATUS_SHIPPED_TO_WORKSHOP,
            'shift_id' => $this->shift->id,
            'initial_quantity' => 100,
        ]);

        foreach ($receivingRolls as $roll) {
            $order->movementMaterials()->create([
                'material_id' => $this->fabricMaterial->id,
                'roll_id' => $roll->id,
                'quantity' => $roll->initial_quantity,
                'ordered_quantity' => 0,
            ]);
        }

        $response = $this->actingAs($this->seamstress)
            ->put(route('movements_to_workshop.save_receive', $order));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Закройте ещё 1 рулонов', session('error'));
    }
}
