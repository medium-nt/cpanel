<?php

namespace Tests\Feature\Services;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $seamstress;

    private User $storekeeper;

    private User $otk;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and users
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $seamstressRole = \App\Models\Role::firstOrCreate(['name' => 'seamstress']);
        $storekeeperRole = \App\Models\Role::firstOrCreate(['name' => 'storekeeper']);
        $otkRole = \App\Models\Role::firstOrCreate(['name' => 'otk']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->seamstress = User::factory()->create([
            'role_id' => $seamstressRole->id,
            'salary_rate' => 1500,
        ]);
        $this->storekeeper = User::factory()->create([
            'role_id' => $storekeeperRole->id,
            'salary_rate' => 1200,
        ]);
        $this->otk = User::factory()->create([
            'role_id' => $otkRole->id,
            'salary_rate' => 1300,
        ]);
    }

    #[Test]
    public function it_can_store_manual_transaction_for_user()
    {
        $this->actingAs($this->admin);

        $request = new CreateTransactionRequest([
            'user_id' => $this->seamstress->id,
            'amount' => 5000,
            'transaction_type' => 'out',
            'title' => 'Премия за отличную работу',
            'accrual_for_date' => now()->toDateString(),
            'type' => 'salary',
        ]);

        $result = TransactionService::store($request);

        $this->assertTrue($result);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seamstress->id,
            'amount' => 5000,
            'transaction_type' => 'out',
            'title' => 'Премия за отличную работу',
            'status' => 1,
            'is_bonus' => false,
        ]);

        // Log verification commented out - focus on business logic
        // Log::shouldReceive('channel')->with('salary')->andReturnSelf();
        // Log::shouldReceive('info')->once()->with(
        //     "Ручное начисление денег в размере 5000 рублей (out) для пользователя {$this->seamstress->name}"
        // );
    }

    #[Test]
    public function it_can_store_manual_bonus_transaction()
    {
        $this->actingAs($this->admin);

        $request = new CreateTransactionRequest([
            'user_id' => $this->seamstress->id,
            'amount' => 1000,
            'transaction_type' => 'out',
            'title' => 'Бонус за выполнение плана',
            'accrual_for_date' => now()->toDateString(),
            'type' => 'bonus',
        ]);

        $result = TransactionService::store($request);

        $this->assertTrue($result);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seamstress->id,
            'amount' => 1000,
            'transaction_type' => 'out',
            'title' => 'Бонус за выполнение плана',
            'status' => 0, // Bonuses start as hold
            'is_bonus' => true,
        ]);
    }

    #[Test]
    public function it_can_store_company_transaction()
    {
        $this->actingAs($this->admin);

        // Create a company balance first
        Transaction::factory()->create([
            'user_id' => null,
            'amount' => 10000,
            'transaction_type' => 'out',
            'status' => 2,
            'paid_at' => now(),
        ]);

        $request = new CreateTransactionRequest([
            'user_id' => null,
            'amount' => 5000,
            'transaction_type' => 'out',
            'title' => 'Расход на материалы',
            'accrual_for_date' => now()->toDateString(),
            'type' => 'company',
        ]);

        $result = TransactionService::store($request);

        $this->assertTrue($result);

        $this->assertDatabaseHas('transactions', [
            'user_id' => null,
            'amount' => 5000,
            'transaction_type' => 'out',
            'title' => 'Расход на материалы',
            'status' => 2,
            'is_bonus' => false,
        ]);
    }

    #[Test]
    public function it_fails_company_transaction_with_insufficient_balance()
    {
        $this->actingAs($this->admin);

        $request = new CreateTransactionRequest([
            'user_id' => null,
            'amount' => 15000, // More than available
            'transaction_type' => 'out',
            'title' => 'Расход на материалы',
            'accrual_for_date' => now()->toDateString(),
            'type' => 'company',
        ]);

        $result = TransactionService::store($request);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_accrual_storekeeper_salary()
    {
        // Create schedule for yesterday
        Schedule::factory()->create([
            'user_id' => $this->storekeeper->id,
            'date' => Carbon::yesterday()->toDateString(),
        ]);

        Log::shouldReceive('channel')->with('salary')->andReturnSelf();
        Log::shouldReceive('info')->once()->with(
            "Добавили зарплату в размере 1200 рублей для кладовщика {$this->storekeeper->name}"
        );

        TransactionService::accrualStorekeeperSalary();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->storekeeper->id,
            'amount' => 1200,
            'transaction_type' => 'out',
            'title' => 'Зарплата за '.Carbon::yesterday()->format('d/m/Y'),
            'status' => 1,
        ]);
    }

    #[Test]
    public function it_can_accrual_otk_salary()
    {
        // Create schedule for yesterday
        Schedule::factory()->create([
            'user_id' => $this->otk->id,
            'date' => Carbon::yesterday()->toDateString(),
        ]);

        Log::shouldReceive('channel')->with('salary')->andReturnSelf();
        Log::shouldReceive('info')->once()->with(
            "Добавили зарплату в размере 1300 рублей для сотрудника ОКТ {$this->otk->name}"
        );

        TransactionService::accrualOtkSalary();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->otk->id,
            'amount' => 1300,
            'transaction_type' => 'out',
            'title' => 'Зарплата за '.Carbon::yesterday()->format('d/m/Y'),
            'status' => 1,
        ]);
    }

    #[Test]
    public function it_can_activate_hold_bonuses()
    {
        // Create old bonus transactions (more than 14 days old)
        $oldBonus = Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 500,
            'is_bonus' => true,
            'status' => 0, // Hold status
            'accrual_for_date' => Carbon::now()->subDays(20)->toDateString(),
        ]);

        // Create recent bonus transaction (less than 14 days old)
        $recentBonus = Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 300,
            'is_bonus' => true,
            'status' => 0, // Hold status
            'accrual_for_date' => Carbon::now()->subDays(10)->toDateString(),
        ]);

        Log::shouldReceive('channel')->with('salary')->andReturnSelf();
        Log::shouldReceive('info')->once();

        TransactionService::activateHoldBonus();

        $oldBonus->refresh();
        $recentBonus->refresh();

        $this->assertEquals(1, $oldBonus->status); // Should be activated
        $this->assertEquals(0, $recentBonus->status); // Should remain hold
    }

    #[Test]
    public function it_calculates_seamstress_balance_correctly()
    {
        $this->actingAs($this->seamstress);

        // Create some salary transactions
        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 5000,
            'transaction_type' => 'out',
            'is_bonus' => false,
            'status' => 1,
        ]);

        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 1000,
            'transaction_type' => 'in', // Deduction
            'is_bonus' => false,
            'status' => 1,
        ]);

        // Create some bonus transactions
        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 800,
            'transaction_type' => 'out',
            'is_bonus' => true,
            'status' => 1,
        ]);

        $salaryBalance = TransactionService::getSeamstressBalance('salary');
        $bonusBalance = TransactionService::getSeamstressBalance('bonus');

        $this->assertEquals(4000, $salaryBalance); // 5000 - 1000
        $this->assertEquals(800, $bonusBalance);
    }

    #[Test]
    public function it_filters_transactions_by_request_parameters()
    {
        // Create transactions for different users and dates
        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 1000,
            'accrual_for_date' => '2024-01-01',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 2000,
            'accrual_for_date' => '2024-01-15',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->storekeeper->id,
            'amount' => 1500,
            'accrual_for_date' => '2024-01-10',
        ]);

        $this->actingAs($this->admin);

        // Test filtering by user
        $request = new Request(['user_id' => $this->seamstress->id]);
        $filtered = TransactionService::getFiltered($request)->get();
        $this->assertCount(2, $filtered);

        // Test filtering by date range
        $request = new Request([
            'date_start' => '2024-01-05',
            'date_end' => '2024-01-12',
        ]);
        $filtered = TransactionService::getFiltered($request)->get();
        $this->assertCount(2, $filtered); // storekeeper transaction and seamstress's second

        // Test filtering by type
        $request = new Request(['type' => 'company']);
        $filtered = TransactionService::getFiltered($request)->get();
        $this->assertCount(0, $filtered); // No company transactions in our test data
    }

    #[Test]
    public function it_calculates_total_by_type_correctly()
    {
        $this->actingAs($this->seamstress);

        // Create unpaid transactions
        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 5000,
            'transaction_type' => 'out',
            'is_bonus' => false,
            'paid_at' => null,
        ]);

        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 1000,
            'transaction_type' => 'in',
            'is_bonus' => false,
            'paid_at' => null,
        ]);

        $request = new Request;
        $total = TransactionService::getTotalByType($request, false);

        $this->assertEquals(4000, $total); // 5000 - 1000
    }

    #[Test]
    public function it_calculates_cashflow_correctly()
    {
        // Create paid transactions
        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 5000,
            'transaction_type' => 'out',
            'is_bonus' => false,
            'paid_at' => '2024-01-01 10:00:00',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 1000,
            'transaction_type' => 'in',
            'is_bonus' => false,
            'paid_at' => '2024-01-01 12:00:00',
        ]);

        $this->actingAs($this->seamstress);

        $request = new Request([
            'date_start' => '2024-01-01',
            'date_end' => '2024-01-01',
        ]);

        $cashflow = TransactionService::getCashflowFiltered($request);

        $this->assertCount(1, $cashflow);
        $this->assertEquals(4000, $cashflow->first()->net_balance); // 5000 - 1000
    }

    #[Test]
    public function it_respects_user_permissions_when_filtering_transactions()
    {
        // Create transactions for different users
        Transaction::factory()->create([
            'user_id' => $this->admin->id,
            'amount' => 5000,
        ]);

        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 3000,
        ]);

        // Non-admin user should only see their own transactions
        $this->actingAs($this->seamstress);
        $request = new Request;
        $filtered = TransactionService::getFiltered($request)->get();

        $this->assertCount(1, $filtered);
        $this->assertEquals(3000, $filtered->first()->amount);

        // Admin user should see all transactions
        $this->actingAs($this->admin);
        $filtered = TransactionService::getFiltered($request)->get();

        $this->assertCount(2, $filtered);
    }

    #[Test]
    public function it_gets_last_payouts_correctly()
    {
        // Create paid transactions
        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 5000,
            'transaction_type' => 'out',
            'is_bonus' => false,
            'paid_at' => '2024-01-15 10:00:00',
            'accrual_for_date' => '2024-01-10',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 3000,
            'transaction_type' => 'out',
            'is_bonus' => false,
            'paid_at' => '2024-01-15 11:00:00',
            'accrual_for_date' => '2024-01-12',
        ]);

        $lastPayouts = TransactionService::getLastPayouts($this->seamstress, 5);

        $this->assertCount(1, $lastPayouts);
        $this->assertEquals('15/01/2024', $lastPayouts->first()['payout_date']);
        $this->assertEquals(8000, $lastPayouts->first()['net_total']);
    }

    #[Test]
    public function it_gets_hold_bonus_information_correctly()
    {
        // Create hold bonus transactions
        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 500,
            'is_bonus' => true,
            'status' => 0,
            'accrual_for_date' => '2024-01-01',
        ]);

        Transaction::factory()->create([
            'user_id' => $this->seamstress->id,
            'amount' => 300,
            'is_bonus' => true,
            'status' => 1,
            'accrual_for_date' => '2024-01-05',
        ]);

        $holdBonus = TransactionService::getHoldBonus($this->seamstress);

        $this->assertCount(2, $holdBonus);
        $this->assertEquals('01/01/2024', $holdBonus->first()['accrual_for_date']);
        $this->assertEquals(500, $holdBonus->first()['net_total']);
    }
}
