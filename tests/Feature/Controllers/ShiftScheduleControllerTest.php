<?php

namespace Tests\Feature\Controllers;

use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Models\Workshop;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShiftScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    }

    /**
     * Store: «Выходной» сохраняется как запись без смены для цеха.
     */
    #[Test]
    public function admin_can_store_day_off(): void
    {
        $workshop = Workshop::factory()->create(['status' => Workshop::STATUS_ACTIVE]);

        $this->actingAs($this->admin)
            ->post(route('shift-schedule.store'), [
                'workshop_id' => $workshop->id,
                'month' => now()->month,
                'year' => now()->year,
                'dates' => [
                    ['date' => now()->addDay()->toDateString(), 'shift_id' => 'day_off'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shift_schedule', [
            'date' => now()->addDay()->toDateString(),
            'shift_id' => null,
            'workshop_id' => $workshop->id,
        ]);
    }

    /**
     * Store: рабочая смена цеха сохраняется корректно.
     */
    #[Test]
    public function admin_can_store_shift(): void
    {
        $workshop = Workshop::factory()->create(['status' => Workshop::STATUS_ACTIVE]);
        $shift = Shift::factory()->create(['workshop_id' => $workshop->id]);

        $this->actingAs($this->admin)
            ->post(route('shift-schedule.store'), [
                'workshop_id' => $workshop->id,
                'month' => now()->month,
                'year' => now()->year,
                'dates' => [
                    ['date' => now()->addDay()->toDateString(), 'shift_id' => $shift->id],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shift_schedule', [
            'date' => now()->addDay()->toDateString(),
            'shift_id' => $shift->id,
            'workshop_id' => $workshop->id,
        ]);
    }

    /**
     * Store: сохранение календаря логируется в канал work_shift.
     */
    #[Test]
    public function storing_shift_schedule_writes_audit_log_to_work_shift_channel(): void
    {
        $workshop = Workshop::factory()->create(['status' => Workshop::STATUS_ACTIVE]);
        $shift = Shift::factory()->create(['workshop_id' => $workshop->id]);

        Log::shouldReceive('channel')->once()->with('work_shift')->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('Сохранён календарь смен', Mockery::on(function ($context) use ($workshop) {
                return $context['workshop_id'] === $workshop->id
                    && $context['updated_by'] === $this->admin->id;
            }));

        $this->actingAs($this->admin)
            ->post(route('shift-schedule.store'), [
                'workshop_id' => $workshop->id,
                'month' => now()->month,
                'year' => now()->year,
                'dates' => [
                    ['date' => now()->addDay()->toDateString(), 'shift_id' => $shift->id],
                ],
            ])
            ->assertRedirect();
    }

    /**
     * Store: смена чужого цеха отклоняется правилом ShiftOrDayOff.
     */
    #[Test]
    public function store_rejects_shift_from_another_workshop(): void
    {
        $workshop1 = Workshop::factory()->create(['status' => Workshop::STATUS_ACTIVE]);
        $workshop2 = Workshop::factory()->create(['status' => Workshop::STATUS_ACTIVE]);
        $foreignShift = Shift::factory()->create(['workshop_id' => $workshop2->id]);

        $this->actingAs($this->admin)
            ->post(route('shift-schedule.store'), [
                'workshop_id' => $workshop1->id,
                'month' => now()->month,
                'year' => now()->year,
                'dates' => [
                    ['date' => now()->addDay()->toDateString(), 'shift_id' => $foreignShift->id],
                ],
            ])
            ->assertSessionHasErrors(['dates.0.shift_id']);
    }
}
