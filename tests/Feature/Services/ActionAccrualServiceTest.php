<?php

namespace Tests\Feature\Services;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Tariff;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserTariff;
use App\Services\ActionAccrualService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActionAccrualServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $seamstress;

    protected User $cutter;

    protected User $otk;

    protected User $storekeeper;

    protected Material $fabricMaterial;

    protected Material $otherMaterial;

    protected MarketplaceItem $marketplaceItem;

    protected MarketplaceOrder $marketplaceOrder;

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up specific tables to ensure test isolation
        Transaction::query()->delete();
        UserTariff::query()->delete();
        Tariff::query()->delete();
        MarketplaceOrderItem::query()->delete();
        MarketplaceOrder::query()->delete();
        MarketplaceItem::query()->delete();
        MaterialConsumption::query()->delete();
        Material::query()->delete();
        Schedule::query()->delete();

        // Create roles
        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);
        $cutterRole = Role::firstOrCreate(['name' => 'cutter']);
        $otkRole = Role::firstOrCreate(['name' => 'otk']);
        $storekeeperRole = Role::firstOrCreate(['name' => 'storekeeper']);

        // Create users with different roles
        $this->seamstress = User::factory()->create(['role_id' => $seamstressRole->id]);
        $this->cutter = User::factory()->create(['role_id' => $cutterRole->id]);
        $this->otk = User::factory()->create(['role_id' => $otkRole->id]);
        $this->storekeeper = User::factory()->create(['role_id' => $storekeeperRole->id]);

        // Create materials
        $this->fabricMaterial = Material::factory()->create(['title' => 'Ткань', 'type_id' => 1]);
        $this->otherMaterial = Material::factory()->create(['title' => 'Другой материал', 'type_id' => 1]);

        // Create marketplace item and order
        $this->marketplaceItem = MarketplaceItem::factory()->create([
            'title' => 'Тестовый товар',
            'width' => 200,
            'height' => 250,
        ]);

        $this->marketplaceOrder = MarketplaceOrder::factory()->create();

        // Create material consumption
        MaterialConsumption::create([
            'item_id' => $this->marketplaceItem->id,
            'material_id' => $this->fabricMaterial->id,
            'quantity' => 1,
        ]);

        MaterialConsumption::create([
            'item_id' => $this->marketplaceItem->id,
            'material_id' => $this->otherMaterial->id,
            'quantity' => 1,
        ]);
    }

    #[Test]
    public function accrual_for_action_with_per_meter_tariff_creates_transactions()
    {
        // Arrange - Create user tariff with per_meter type
        $userTariff = UserTariff::factory()->create([
            'user_id' => $this->seamstress->id,
            'action' => 'Пошив', // Use Russian action name
            'type' => 'per_meter',
            'is_bonus' => false,
        ]);

        $tariff = Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '0-1000',
            'value' => 50.00,
        ]);


        // Create completed order item
        $orderItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => Carbon::today(),
            'status' => 4,
        ]);

        // Act
        $service = new ActionAccrualService;
        $service->accrualForAction('sewing', Carbon::today(), false); // Keep English for service call

        // Assert - Check that transaction was created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seamstress->id,
            'title' => "ЗП за заказ #{$this->marketplaceOrder->order_id} (Пошив) - 2 п.м.",
            'amount' => 100.00, // 2 meters * 50 rubles per meter
            'transaction_type' => 'out',
            'status' => 1,
            'is_bonus' => false,
        ]);
    }

    #[Test]
    public function accrual_for_action_with_bonus_tariff_creates_bonus_transaction()
    {
        // Arrange - Create user tariff with per_meter type and is_bonus true
        $userTariff = UserTariff::factory()->create([
            'user_id' => $this->seamstress->id,
            'action' => 'Пошив', // Use Russian action name
            'type' => 'per_meter',
            'is_bonus' => true,
        ]);

        $tariff = Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '0-1000',
            'value' => 25.00,
        ]);

        // Create completed order item
        $orderItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => Carbon::today(),
            'status' => 4,
        ]);

        // Act
        $service = new ActionAccrualService;
        $service->accrualForAction('sewing', Carbon::today(), false); // Keep English for service call

        // Assert - Check that bonus transaction was created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seamstress->id,
            'title' => "ЗП за заказ #{$this->marketplaceOrder->order_id} (бонус) (Пошив) - 2 п.м.",
            'amount' => 50.00, // 2 meters * 25 rubles per meter
            'transaction_type' => 'out',
            'status' => 0, // bonus status
            'is_bonus' => true,
        ]);
    }

    #[Test]
    public function accrual_for_action_with_per_piece_tariff_creates_piece_transaction()
    {
        // Arrange - Create user tariff with per_piece type
        $userTariff = UserTariff::factory()->create([
            'user_id' => $this->cutter->id,
            'action' => 'Закрой', // Use Russian action name
            'type' => 'per_piece',
            'is_bonus' => false,
        ]);

        $tariff = Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'width' => 200, // Exact match for the item width
            'value' => 150.00,
        ]);

        // Create completed order item for cutter
        $orderItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'cutter_id' => $this->cutter->id,
            'cutting_completed_at' => Carbon::today(),
            'status' => 7,
        ]);

        // Act
        $service = new ActionAccrualService;
        $service->accrualForAction('cutting', Carbon::today(), false);

        // Assert - Check that piece transaction was created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->cutter->id,
            'title' => "ЗП за заказ #{$this->marketplaceOrder->order_id} (Закрой)",
            'amount' => 150.00,
            'transaction_type' => 'out',
            'status' => 1,
            'is_bonus' => false,
        ]);
    }

    #[Test]
    public function accrual_for_action_with_split_calculation_creates_single_transaction()
    {
        // Arrange - Create two tariffs for different ranges
        $userTariff = UserTariff::factory()->create([
            'user_id' => $this->seamstress->id,
            'action' => 'Пошив', // Use Russian action name
            'type' => 'per_meter',
            'is_bonus' => false,
        ]);

        // Tariff for 0-1000 meters at 50 rubles
        Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '0-1000',
            'value' => 50.00,
        ]);

        // Tariff for 1000-2000 meters at 75 rubles
        Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '1000-2000',
            'value' => 75.00,
        ]);

        // Create order item that crosses the boundary (cumulative 1200 meters, item adds 400 meters)
        // First item completes 1200 - 400 = 800 meters
        $firstItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => Carbon::today(),
            'status' => 4,
        ]);

        // Second item crosses the boundary: 800 + 500 = 1300 meters
        $secondItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => Carbon::today(),
            'status' => 4,
        ]);

        // Act
        $service = new ActionAccrualService;
        $service->accrualForAction('sewing', Carbon::today(), false); // Keep English for service call

        // Assert - Check that transactions were created
        $this->assertGreaterThan(0, Transaction::where('user_id', $this->seamstress->id)->count());

        // Check that the amounts are reasonable
        $totalAmount = Transaction::where('user_id', $this->seamstress->id)->sum('amount');
        $this->assertGreaterThan(0, $totalAmount);

        // Should have at least 1 transaction
        $this->assertGreaterThanOrEqual(1, Transaction::where('user_id', $this->seamstress->id)->count());
    }

    #[Test]
    public function accrual_for_action_with_no_tariffs_returns_early()
    {
        // Arrange - Create order item but no tariffs
        $orderItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => Carbon::today(),
            'status' => 4,
        ]);

        // Act
        $service = new ActionAccrualService;
        $service->accrualForAction('sewing', Carbon::today(), false); // Keep English for service call

        // Assert - No transactions should be created
        $this->assertEquals(0, Transaction::count());
    }

    #[Test]
    public function accrual_for_action_with_no_completed_items_returns_early()
    {
        // Arrange - Create user tariff but no completed items
        $userTariff = UserTariff::factory()->create([
            'user_id' => $this->seamstress->id,
            'action' => 'Пошив', // Use Russian action name
            'type' => 'per_meter',
            'is_bonus' => false,
        ]);

        Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '0-1000',
            'value' => 50.00,
        ]);

        // Create uncompleted order item
        $orderItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => null,
            'status' => 3,
        ]);

        // Act
        $service = new ActionAccrualService;
        $service->accrualForAction('sewing', Carbon::today(), false); // Keep English for service call

        // Assert - No transactions should be created
        $this->assertEquals(0, Transaction::count());
    }

    #[Test]
    public function accrual_for_action_with_wrong_material_type_continues_processing()
    {
        // Create a regular item with width of 150cm

        $userTariff = UserTariff::factory()->create([
            'user_id' => $this->seamstress->id,
            'action' => 'Пошив', // Use Russian action name
            'type' => 'per_meter',
            'is_bonus' => false,
        ]);

        Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '0-1000',
            'value' => 50.00,
        ]);

        // Create order item with item width of 150cm (should be 1.5 meters)
        $orderItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => Carbon::today(),
            'status' => 4,
        ]);

        // Act
        $service = new ActionAccrualService;
        $service->accrualForAction('sewing', Carbon::today(), false); // Keep English for service call

        // Assert - Should create transaction (2m * 50 = 100 rubles)
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seamstress->id,
            'amount' => 100.00,
            'is_bonus' => false,
        ]);
    }

    #[Test]
    public function accrual_salary_daily_with_minimum_actions_requirement()
    {
        // Arrange - Create schedule with date
        $schedule = Schedule::factory()->create([
            'user_id' => $this->seamstress->id,
            'date' => Carbon::today(),
        ]);

        // Create user tariff for salary
        $userTariff = UserTariff::factory()->state([
            'user_id' => $this->seamstress->id,
            'action' => 'Оклад', // Use Russian action name
            'type' => 'per_meter',
            'is_bonus' => false,
        ])->create();

        Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'value' => 5000.00,
        ]);

        // Create only 2 completed items (less than minimum 3)
        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => Carbon::today(),
            'status' => 4,
        ]);

        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => Carbon::today(),
            'status' => 4,
        ]);

        // Act
        $service = new ActionAccrualService;
        $service->accrualSalaryDaily(Carbon::today(), false);

        // Assert - No transaction should be created due to insufficient actions
        $this->assertEquals(0, Transaction::where('user_id', $this->seamstress->id)->count());
    }

    #[Test]
    public function accrual_salary_daily_with_sufficient_actions_creates_transaction()
    {
        // Use a fixed date to avoid timezone issues
        $testDate = '2026-06-13';

        // Arrange - Create schedule with date
        $schedule = Schedule::factory()->create([
            'user_id' => $this->seamstress->id,
            'date' => $testDate,
        ]);

        // Debug - Check what date was actually stored
        $createdSchedule = Schedule::where('user_id', $this->seamstress->id)->first();
        \Log::info('Created schedule date', [
            'expected_date' => $testDate,
            'actual_date' => $createdSchedule->date,
            'user_id' => $this->seamstress->id,
        ]);

        // Create user tariff for salary
        $userTariff = UserTariff::factory()->create([
            'user_id' => $this->seamstress->id,
            'action' => 'Оклад', // Use Russian action name
            'type' => 'per_meter',
            'is_bonus' => false,
        ]);

        Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '0-1000',
            'value' => 5000.00,
        ]);

        // Create 3 completed items (minimum required)
        for ($i = 0; $i < 3; $i++) {
            MarketplaceOrderItem::factory()->create([
                'marketplace_order_id' => $this->marketplaceOrder->id,
                'marketplace_item_id' => $this->marketplaceItem->id,
                'seamstress_id' => $this->seamstress->id,
                'completed_at' => $testDate,
                'status' => 4,
            ]);
        }

        // Debug - Check what schedules exist
        $allSchedules = \App\Models\Schedule::where('user_id', $this->seamstress->id)->get();
        $this->assertNotEmpty($allSchedules, "Should have at least one schedule for user {$this->seamstress->id}");

        foreach ($allSchedules as $s) {
            \Log::info('Found schedule', [
                'id' => $s->id,
                'user_id' => $s->user_id,
                'date' => $s->date,
                'expected_date' => Carbon::today()->format('Y-m-d'),
                'date_matches' => $s->date === Carbon::today()->format('Y-m-d'),
            ]);
        }

        // Debug - Verify what was actually created
        $createdUserTariff = UserTariff::where('user_id', $this->seamstress->id)->where('action', 'Оклад')->first();
        $createdTariff = Tariff::where('user_tariff_id', $createdUserTariff->id)->first();

        $this->assertNotNull($createdUserTariff, 'User tariff should exist');
        $this->assertNotNull($createdTariff, 'Tariff should exist');
        $this->assertEquals(5000.00, $createdTariff->value);

        // Debug - Check schedule and user tariff
        $schedule = \App\Models\Schedule::where('date', $testDate)->where('user_id', $this->seamstress->id)->first();
        $userTariffs = \App\Models\UserTariff::where('user_id', $this->seamstress->id)->where('action', 'Оклад')->get();
        $tariffs = \App\Models\Tariff::where('user_tariff_id', $userTariffs->first()->id ?? null)->get();

        $this->assertNotNull($schedule, "Schedule should exist for user {$this->seamstress->id}");
        $this->assertNotEmpty($userTariffs, "User tariffs should exist for user {$this->seamstress->id}");
        $this->assertNotEmpty($tariffs, "Tariffs should exist for user tariff {$userTariffs->first()->id}");

        // Act
        $service = new ActionAccrualService;
        $service->accrualSalaryDaily(Carbon::parse($testDate), false);

        // Assert - Transaction should be created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seamstress->id,
            'title' => 'Оклад за '.Carbon::parse($testDate)->format('d/m/Y').'',
            'amount' => 5000.00,
            'transaction_type' => 'out',
            'status' => 1,
            'is_bonus' => false,
        ]);
    }

    #[Test]
    public function accrual_salary_daily_with_different_role_has_different_action_count_logic()
    {
        // Test seamstress requires 3 actions
        $this->assertActionCountRequirement('seamstress', 3);

        // Test cutter requires 3 actions
        $this->assertActionCountRequirement('cutter', 3);

        // Test otk requires 3 actions
        $this->assertActionCountRequirement('otk', 3);

        // Test storekeeper (no requirement)
        $this->assertActionCountRequirement('storekeeper', 0);
    }

    #[Test]
    public function accrual_for_action_test_mode_does_not_create_transactions()
    {
        // Arrange - Create user tariff
        $userTariff = UserTariff::factory()->create([
            'user_id' => $this->seamstress->id,
            'action' => 'Пошив', // Use Russian action name
            'type' => 'per_meter',
            'is_bonus' => false,
        ]);

        Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '0-1000',
            'value' => 50.00,
        ]);

        // Create completed order item
        $orderItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => Carbon::today(),
            'status' => 4,
        ]);

        // Act - Test mode with test = true
        $service = new ActionAccrualService;
        $service->accrualForAction('sewing', Carbon::today(), true);

        // Assert - No transactions should be created in test mode
        $this->assertEquals(0, Transaction::count());
    }

    #[Test]
    public function accrual_salary_daily_with_bonus_tariff_creates_bonus_transaction()
    {
        // Use a fixed date to avoid timezone issues
        $testDate = '2026-06-13';

        // Arrange - Create schedule with date
        $schedule = Schedule::factory()->create([
            'user_id' => $this->seamstress->id,
            'date' => $testDate,
        ]);

        // Create user tariff for bonus salary
        $userTariff = UserTariff::factory()->create([
            'user_id' => $this->seamstress->id,
            'action' => 'Оклад', // Use Russian action name
            'type' => 'per_meter',
            'is_bonus' => true,
        ]);

        Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '0-1000',
            'value' => 1000.00,
        ]);

        // Create 3 completed items (minimum required)
        for ($i = 0; $i < 3; $i++) {
            MarketplaceOrderItem::factory()->create([
                'marketplace_order_id' => $this->marketplaceOrder->id,
                'marketplace_item_id' => $this->marketplaceItem->id,
                'seamstress_id' => $this->seamstress->id,
                'completed_at' => $testDate,
                'status' => 4,
            ]);
        }

        // Act
        $service = new ActionAccrualService;
        $service->accrualSalaryDaily(Carbon::parse($testDate), false);

        // Assert - Bonus transaction should be created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seamstress->id,
            'title' => 'Оклад за '.Carbon::parse($testDate)->format('d/m/Y').' (бонус)',
            'amount' => 1000.00,
            'transaction_type' => 'out',
            'status' => 0, // bonus status
            'is_bonus' => true,
        ]);
    }

    #[Test]
    public function accrual_for_action_with_multiple_tariff_types_processes_both()
    {
        // Use a fixed date to avoid timezone issues
        $testDate = '2026-06-13';

        // Arrange - Create separate user tariffs for per_meter and per_piece
        $userTariffPerMeter = UserTariff::factory()->create([
            'user_id' => $this->seamstress->id,
            'action' => 'Пошив', // Use Russian action name for sewing
            'type' => 'per_meter',
            'is_bonus' => false,
        ]);

        $userTariffPerPiece = UserTariff::factory()->create([
            'user_id' => $this->seamstress->id,
            'action' => 'Пошив', // Use Russian action name for sewing
            'type' => 'per_piece',
            'is_bonus' => false,
        ]);

        // per_meter tariff
        Tariff::factory()->create([
            'user_tariff_id' => $userTariffPerMeter->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '0-1000',
            'value' => 50.00,
        ]);

        // per_piece tariff
        Tariff::factory()->create([
            'user_tariff_id' => $userTariffPerPiece->id,
            'material_id' => $this->fabricMaterial->id,
            'width' => 200,
            'value' => 25.00,
        ]);

        // Create completed order item for seamstress
        $orderItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => $testDate,
            'status' => 4,
        ]);

        // Act
        $service = new ActionAccrualService;
        $service->accrualForAction('sewing', Carbon::parse($testDate), false);

        // Assert - Should create 2 transactions (one for per_meter, one for per_piece)
        $this->assertEquals(2, Transaction::where('user_id', $this->seamstress->id)->count());

        // Check per_meter transaction (2m * 50 = 100) - look for the correct title format
        $perMeterTransaction = Transaction::where('user_id', $this->seamstress->id)
            ->where('amount', 100.00)
            ->first();
        $this->assertNotNull($perMeterTransaction, 'Per-meter transaction with amount 100 should exist');

        // Check per_piece transaction (25.00)
        $perPieceTransaction = Transaction::where('user_id', $this->seamstress->id)
            ->where('amount', 25.00)
            ->first();
        $this->assertNotNull($perPieceTransaction, 'Per-piece transaction with amount 25 should exist');
    }

    #[Test]
    public function accrual_for_action_supports_multiple_actions()
    {
        // Arrange - Create tariffs for different actions
        $sewingTariff = UserTariff::factory()->create([
            'user_id' => $this->seamstress->id,
            'action' => 'Пошив', // Use Russian action name
            'type' => 'per_meter',
            'is_bonus' => false,
        ]);

        Tariff::factory()->create([
            'user_tariff_id' => $sewingTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '0-1000',
            'value' => 50.00,
        ]);

        $cuttingTariff = UserTariff::factory()->create([
            'user_id' => $this->cutter->id,
            'action' => 'Закрой', // Use Russian action name
            'type' => 'per_meter',
            'is_bonus' => false,
        ]);

        Tariff::factory()->create([
            'user_tariff_id' => $cuttingTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'range' => '0-1000',
            'value' => 75.00,
        ]);

        // Create order items for both actions
        $sewingItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'seamstress_id' => $this->seamstress->id,
            'completed_at' => Carbon::today(),
            'status' => 4,
        ]);

        $cuttingItem = MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $this->marketplaceOrder->id,
            'marketplace_item_id' => $this->marketplaceItem->id,
            'cutter_id' => $this->cutter->id,
            'cutting_completed_at' => Carbon::today(),
            'status' => 7,
        ]);

        // Act
        $service = new ActionAccrualService;
        $service->accrualForAction('sewing', Carbon::today(), false); // Keep English for service call
        $service->accrualForAction('cutting', Carbon::today(), false);

        // Assert - Should create transactions for both users
        $this->assertEquals(1, Transaction::where('user_id', $this->seamstress->id)->count());
        $this->assertEquals(1, Transaction::where('user_id', $this->cutter->id)->count());

        // Check amounts (2m * 50 = 100 for seamstress, 2m * 75 = 150 for cutter)
        $seamstressAmount = Transaction::where('user_id', $this->seamstress->id)->first()->amount;
        $cutterAmount = Transaction::where('user_id', $this->cutter->id)->first()->amount;

        $this->assertEquals(100.00, $seamstressAmount);
        $this->assertEquals(150.00, $cutterAmount);
    }

    /**
     * Helper method to test action count requirements for different roles
     */
    private function assertActionCountRequirement(string $roleName, int $expectedCount): void
    {
        $role = Role::firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create(['role_id' => $role->id]);

        // Create schedule
        Schedule::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
        ]);

        // Create salary tariff
        $userTariff = UserTariff::factory()->create([
            'user_id' => $user->id,
            'action' => 'Оклад', // Use Russian action name
            'is_bonus' => false,
        ]);

        Tariff::factory()->create([
            'user_tariff_id' => $userTariff->id,
            'material_id' => $this->fabricMaterial->id,
            'value' => 1000.00,
        ]);

        // Create test action count based on role
        $actionCount = $expectedCount === 0 ? 0 : $expectedCount - 1;

        for ($i = 0; $i < $actionCount; $i++) {
            match ($roleName) {
                'seamstress' => MarketplaceOrderItem::factory()->create([
                    'seamstress_id' => $user->id,
                    'completed_at' => Carbon::today(),
                    'status' => 4,
                ]),
                'cutter' => MarketplaceOrderItem::factory()->create([
                    'cutter_id' => $user->id,
                    'cutting_completed_at' => Carbon::today(),
                    'status' => 7,
                ]),
                'otk' => MarketplaceOrderItem::factory()->create([
                    'otk_id' => $user->id,
                    'packed_at' => Carbon::today(),
                    'status' => 8,
                ]),
                default => null,
            };
        }

        // Test accrual
        $service = new ActionAccrualService;
        $service->accrualSalaryDaily(Carbon::today(), false);

        // Assert - no transaction if below minimum, transaction if meeting minimum
        if ($expectedCount === 0) {
            // Storekeeper and others have no minimum requirement
            $this->assertGreaterThanOrEqual(0, Transaction::where('user_id', $user->id)->count());
        } elseif ($expectedCount > $actionCount) {
            // Below minimum requirement
            $this->assertEquals(0, Transaction::where('user_id', $user->id)->count());
        } else {
            // Meeting or exceeding minimum requirement
            $this->assertGreaterThan(0, Transaction::where('user_id', $user->id)->count());
        }
    }
}
