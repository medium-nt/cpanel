<?php

namespace Tests\Unit\Services;

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use App\Models\Shift;
use App\Models\Workshop;
use App\Services\AutoOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private Workshop $workshop;

    private Shift $shift;

    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        // Отключаем сидерные материалы — тесты работают с контролируемым набором
        Material::query()->update(['is_active' => false]);

        // Создаём тестовый цех — все смены и заказы привязаны к нему
        $this->workshop = Workshop::create([
            'title' => 'Тестовый цех',
            'status' => Workshop::STATUS_ACTIVE,
        ]);

        $this->shift = Shift::create([
            'workshop_id' => $this->workshop->id,
            'name' => 'Тестовая смена',
            'status' => Shift::STATUS_ACTIVE,
        ]);

        $this->material = Material::factory()->create([
            'title' => 'Тестовая упаковка',
            'unit' => 'м',
            'is_active' => true,
        ]);

        // Привязываем материал к цеху — сервис заказывает только разрешённые материалы
        $this->workshop->allowedMaterials()->attach($this->material);
    }

    /** Создать рулон в цехе у данной смены. */
    private function createWorkshopRoll(float $quantity, ?Shift $shift = null, ?Material $material = null): Roll
    {
        return Roll::create([
            'shift_id' => $shift?->id ?? $this->shift->id,
            'roll_code' => 'ROLL-'.uniqid(),
            'material_id' => $material?->id ?? $this->material->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'initial_quantity' => $quantity,
        ]);
    }

    public function test_auto_order_created_when_quantity_below_threshold(): void
    {
        $this->createWorkshopRoll(80);

        $result = AutoOrderService::checkAndCreateAutoOrders();

        $this->assertCount(1, $result);
        $this->assertDatabaseHas('orders', [
            'type_movement' => 2,
            'status' => 0,
            'comment' => '[Автозаказ]',
            'shift_id' => $this->shift->id,
        ]);
        $this->assertDatabaseHas('movement_materials', [
            'material_id' => $this->material->id,
            'ordered_quantity' => 0,
        ]);
    }

    public function test_auto_order_not_created_when_quantity_above_threshold(): void
    {
        $this->createWorkshopRoll(200);

        $result = AutoOrderService::checkAndCreateAutoOrders();

        $this->assertEmpty($result);
        $this->assertDatabaseMissing('orders', [
            'comment' => '[Автозаказ]',
        ]);
    }

    public function test_auto_order_not_created_when_duplicate_exists(): void
    {
        $this->createWorkshopRoll(50);

        $existingOrder = Order::factory()->create([
            'type_movement' => 2,
            'status' => 0,
            'shift_id' => $this->shift->id,
            'comment' => 'Ручной заказ',
        ]);
        MovementMaterial::factory()->create([
            'material_id' => $this->material->id,
            'ordered_quantity' => 0,
            'order_id' => $existingOrder->id,
            'quantity' => 0,
        ]);

        $result = AutoOrderService::checkAndCreateAutoOrders();

        $this->assertEmpty($result);
        $this->assertDatabaseMissing('orders', [
            'comment' => '[Автозаказ]',
            'shift_id' => $this->shift->id,
        ]);
    }

    public function test_auto_order_not_created_when_no_active_shifts(): void
    {
        $this->shift->update(['status' => Shift::STATUS_INACTIVE]);

        Roll::create([
            'shift_id' => $this->shift->id,
            'roll_code' => 'ROLL-'.uniqid(),
            'material_id' => $this->material->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'initial_quantity' => 50,
        ]);

        $result = AutoOrderService::checkAndCreateAutoOrders();

        $this->assertEmpty($result);
    }

    public function test_auto_order_created_for_material_with_zero_quantity_in_workshop(): void
    {
        // Материал активный, но рулонов в цехе нет — остаток = 0 → автозаказ

        $result = AutoOrderService::checkAndCreateAutoOrders();

        $this->assertCount(1, $result);
        $this->assertDatabaseHas('orders', [
            'type_movement' => 2,
            'status' => 0,
            'comment' => '[Автозаказ]',
            'shift_id' => $this->shift->id,
        ]);
    }

    public function test_auto_order_not_created_for_inactive_material(): void
    {
        // Активный материал выше порога — не должен дать автозаказ
        $this->createWorkshopRoll(200);

        $inactiveMaterial = Material::factory()->create([
            'title' => 'Отключённая упаковка',
            'unit' => 'м',
            'is_active' => false,
        ]);

        $result = AutoOrderService::checkAndCreateAutoOrders();

        $this->assertEmpty($result);
        $this->assertDatabaseMissing('movement_materials', [
            'material_id' => $inactiveMaterial->id,
        ]);
    }

    public function test_auto_order_created_for_correct_shift_only(): void
    {
        // Один и тот же материал: у shift1 = 80 (ниже), у shift2 = 200 (выше)
        $shift2 = Shift::create([
            'workshop_id' => $this->workshop->id,
            'name' => 'Вторая смена',
            'status' => Shift::STATUS_ACTIVE,
        ]);

        $this->createWorkshopRoll(80, $this->shift);

        Roll::create([
            'shift_id' => $shift2->id,
            'roll_code' => 'ROLL-'.uniqid(),
            'material_id' => $this->material->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'initial_quantity' => 200,
        ]);

        $result = AutoOrderService::checkAndCreateAutoOrders();

        // Только shift1 + material → автозаказ (80 <= 100)
        $this->assertCount(1, $result);
        $this->assertDatabaseHas('orders', [
            'type_movement' => 2,
            'status' => 0,
            'shift_id' => $this->shift->id,
            'comment' => '[Автозаказ]',
        ]);
        // shift2 + material → нет автозаказа (200 > 100)
        $this->assertDatabaseMissing('orders', [
            'shift_id' => $shift2->id,
            'comment' => '[Автозаказ]',
        ]);
    }

    public function test_auto_order_created_when_exactly_at_threshold(): void
    {
        $this->createWorkshopRoll(100);

        $result = AutoOrderService::checkAndCreateAutoOrders();

        $this->assertCount(1, $result);
    }
}
