<?php

namespace Tests\Feature\Controllers;

use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        $this->employee = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'seamstress'])->id,
        ]);
    }

    /**
     * Выплата зарплаты должна писать аудит-лог в канал salary
     * с идентификатором сотрудника, количеством строк и суммой.
     */
    #[Test]
    public function payout_salary_writes_audit_log_to_salary_channel()
    {
        $date = now()->toDateString();

        // Неоплаченное начисление сотруднику (transaction_type 'out').
        Transaction::factory()->create([
            'user_id' => $this->employee->id,
            'is_bonus' => false,
            'transaction_type' => 'out',
            'amount' => 100,
            'accrual_for_date' => $date,
            'paid_at' => null,
            'status' => 0,
        ]);

        // Оплаченный приход в кассу компании — чтобы пройти гард «достаточно денег».
        Transaction::factory()->create([
            'user_id' => $this->admin->id,
            'is_bonus' => false,
            'transaction_type' => 'in',
            'amount' => 1000,
            'accrual_for_date' => $date,
            'paid_at' => now(),
            'status' => 2,
        ]);

        Log::shouldReceive('channel')->once()->with('salary')->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('Выплата зарплаты', Mockery::on(function ($context) {
                return $context['user_id'] === $this->employee->id
                    && $context['rows'] == 1
                    && $context['sum'] == 100
                    && $context['paid_by'] === $this->admin->id;
            }));

        $this->actingAs($this->admin)
            ->post(route('transactions.store_payout_salary'), [
                'user_id' => $this->employee->id,
                'start_date' => $date,
                'end_date' => $date,
            ])
            ->assertRedirect();
    }
}
