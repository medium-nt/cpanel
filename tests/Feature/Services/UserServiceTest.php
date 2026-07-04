<?php

namespace Tests\Feature\Services;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $seamstress;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'start_work_shift' => '08:00:00',
            'max_late_minutes' => 15,
            'shift_is_open' => false,
        ]);

        $this->seamstress = User::factory()->create([
            'role_id' => $seamstressRole->id,
            'start_work_shift' => '08:00:00',
            'max_late_minutes' => 15,
            'shift_is_open' => false,
        ]);

        Setting::updateOrCreate(['name' => 'late_opened_shift_penalty'], ['value' => '500']);
        Setting::updateOrCreate(['name' => 'unclosed_shift_penalty'], ['value' => '1000']);
    }

    // ─── checkLateStartWorkShift ───────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_does_not_get_penalty_for_late_shift_opening(): void
    {
        Carbon::setTestNow(Carbon::parse('10:00:00'));

        UserService::checkLateStartWorkShift($this->admin);

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $this->admin->id,
            'title' => 'Штраф за опоздание на смену '.now()->format('d/m/Y'),
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function seamstress_gets_penalty_for_late_shift_opening(): void
    {
        Carbon::setTestNow(Carbon::parse('10:00:00'));

        UserService::checkLateStartWorkShift($this->seamstress);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seamstress->id,
            'amount' => 500,
            'transaction_type' => 'in',
            'status' => 1,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function seamstress_does_not_get_penalty_when_on_time(): void
    {
        Carbon::setTestNow(Carbon::parse('08:10:00'));

        UserService::checkLateStartWorkShift($this->seamstress);

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $this->seamstress->id,
        ]);
    }

    // ─── checkUnclosedWorkShifts ───────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_shift_closes_without_penalty(): void
    {
        $this->admin->update(['shift_is_open' => true]);

        UserService::checkUnclosedWorkShifts();

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $this->admin->id,
        ]);

        $this->admin->refresh();
        $this->assertEquals(0, $this->admin->shift_is_open);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function seamstress_shift_closes_with_penalty(): void
    {
        $this->seamstress->update(['shift_is_open' => true]);

        UserService::checkUnclosedWorkShifts();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->seamstress->id,
            'amount' => 1000,
            'transaction_type' => 'in',
            'status' => 1,
        ]);

        $this->seamstress->refresh();
        $this->assertEquals(0, $this->seamstress->shift_is_open);
    }

    // ─── getConnectedToMaxUsers ────────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function get_connected_to_max_users_returns_only_those_with_max_id(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Подключённые
        User::factory()->create(['role_id' => $adminRole->id, 'max_id' => '100', 'name' => 'Баранов Борис Борисович']);
        User::factory()->create(['role_id' => $adminRole->id, 'max_id' => '200', 'name' => 'Александров Алексей Алексеевич']);

        // НЕ подключённые
        User::factory()->create(['role_id' => $adminRole->id, 'max_id' => null]);
        User::factory()->create(['role_id' => $adminRole->id, 'max_id' => '']);

        $connected = UserService::getConnectedToMaxUsers();

        $this->assertCount(2, $connected);
        // Сортировка по name по возрастанию.
        $this->assertEquals('Александров Алексей Алексеевич', $connected->first()->name);
        $this->assertEquals('Баранов Борис Борисович', $connected->last()->name);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
