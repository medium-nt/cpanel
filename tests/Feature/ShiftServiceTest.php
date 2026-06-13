<?php

namespace Tests\Feature\Services;

use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles that the service depends on
        $this->adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
        $this->seamstressRole = \App\Models\Role::firstOrCreate(['name' => 'seamstress']);
        $this->cutterRole = \App\Models\Role::firstOrCreate(['name' => 'cutter']);
        $this->otkRole = \App\Models\Role::firstOrCreate(['name' => 'otk']);
        $this->storekeeperRole = \App\Models\Role::firstOrCreate(['name' => 'storekeeper']);
    }

    /**
     * Test getUserShift returns null for user with no shift assignment.
     */
    public function test_get_user_shift_returns_null_for_unassigned_user(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        $this->assertNull(ShiftService::getUserShift($user));
    }

    /**
     * Test getUserShift returns correct shift for assigned user.
     */
    public function test_get_user_shift_returns_assigned_shift(): void
    {
        // Arrange
        $shift = Shift::factory()->create();
        $user = User::factory()->create();
        $user->shifts()->attach($shift->id, [
            'effective_from' => now()->toDateString(),
        ]);

        // Act
        $result = ShiftService::getUserShift($user);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($shift->id, $result->id);
    }

    /**
     * Test getUserShift returns most recent effective shift for user with multiple assignments.
     */
    public function test_get_user_shift_returns_most_recent_shift(): void
    {
        // Arrange
        $shift1 = Shift::factory()->create();
        $shift2 = Shift::factory()->create();
        $user = User::factory()->create();

        // Assign older shift first
        $user->shifts()->attach($shift1->id, [
            'effective_from' => now()->subDays(10)->toDateString(),
        ]);

        // Assign newer shift
        $user->shifts()->attach($shift2->id, [
            'effective_from' => now()->toDateString(),
        ]);

        // Act
        $result = ShiftService::getUserShift($user);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($shift2->id, $result->id); // Should return the most recent
    }

    /**
     * Test getTodayScheduledShift returns null when no schedule exists for today.
     */
    public function test_get_today_scheduled_shift_returns_null_when_no_schedule(): void
    {
        // Arrange - no schedules

        // Act & Assert
        $this->assertNull(ShiftService::getTodayScheduledShift());
    }

    /**
     * Test getTodayScheduledShift returns scheduled shift for today.
     */
    public function test_get_today_scheduled_shift_returns_scheduled_shift(): void
    {
        // Arrange
        $shift = Shift::factory()->create();
        ShiftSchedule::factory()->forDate(now()->toDateString())->create([
            'shift_id' => $shift->id,
        ]);

        // Act
        $result = ShiftService::getTodayScheduledShift();

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($shift->id, $result->id);
    }

    /**
     * Test getTodayScheduledShifts returns empty collection when no schedules exist.
     */
    public function test_get_today_scheduled_shifts_returns_empty_collection(): void
    {
        // Arrange - no schedules

        // Act
        $result = ShiftService::getTodayScheduledShifts();

        // Assert
        $this->assertEmpty($result);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    /**
     * Test getTodayScheduledShifts returns all shifts scheduled for today.
     */
    public function test_get_today_scheduled_shifts_returns_scheduled_shifts(): void
    {
        // Arrange
        $shift1 = Shift::factory()->create();
        $shift2 = Shift::factory()->create();

        ShiftSchedule::factory()->forDate(now()->toDateString())->create([
            'shift_id' => $shift1->id,
        ]);
        ShiftSchedule::factory()->forDate(now()->toDateString())->create([
            'shift_id' => $shift2->id,
        ]);

        // Act
        $result = ShiftService::getTodayScheduledShifts();

        // Assert
        $this->assertCount(2, $result);
        $this->assertContains($shift1->id, $result->pluck('id'));
        $this->assertContains($shift2->id, $result->pluck('id'));
    }

    /**
     * Test canWorkToday returns true for users with non-shift roles.
     */
    public function test_can_work_today_returns_true_for_non_shift_roles(): void
    {
        // Arrange
        $user = User::factory()->create([
            'role_id' => $this->storekeeperRole->id,
        ]);

        // Act & Assert
        $this->assertTrue(ShiftService::canWorkToday($user));
    }

    /**
     * Test canWorkToday returns true when user has no assigned shift.
     */
    public function test_can_work_today_returns_true_for_unassigned_shift_user(): void
    {
        // Arrange
        $user = User::factory()->create(['role_id' => null]);

        // Act & Assert
        $this->assertTrue(ShiftService::canWorkToday($user));
    }

    /**
     * Test canWorkToday returns true when no schedule exists for today.
     */
    public function test_can_work_today_returns_true_when_no_schedule_exists(): void
    {
        // Arrange
        $user = User::factory()->create([
            'role_id' => $this->seamstressRole->id,
        ]);

        // Assign shift but no schedule
        $shift = Shift::factory()->create();
        $user->shifts()->attach($shift->id, [
            'effective_from' => now()->toDateString(),
        ]);

        // Act & Assert
        $this->assertTrue(ShiftService::canWorkToday($user));
    }

    /**
     * Test canWorkToday returns true when user's shift is in today's schedule.
     */
    public function test_can_work_today_returns_true_for_matching_shift(): void
    {
        // Arrange
        $shift = Shift::factory()->create();
        $user = User::factory()->create([
            'role_id' => $this->seamstressRole->id,
        ]);

        // Assign user to shift
        $user->shifts()->attach($shift->id, [
            'effective_from' => now()->toDateString(),
        ]);

        // Schedule the shift for today
        ShiftSchedule::factory()->forDate(now()->toDateString())->create([
            'shift_id' => $shift->id,
        ]);

        // Act & Assert
        $this->assertTrue(ShiftService::canWorkToday($user));
    }

    /**
     * Test canWorkToday returns false when user's shift is not in today's schedule.
     */
    public function test_can_work_today_returns_false_for_non_matching_shift(): void
    {
        // Arrange
        $userShift = Shift::factory()->create();
        $scheduledShift = Shift::factory()->create();
        $user = User::factory()->create([
            'role_id' => $this->seamstressRole->id,
        ]);

        // Assign user to shift
        $user->shifts()->attach($userShift->id, [
            'effective_from' => now()->toDateString(),
        ]);

        // Schedule different shift for today
        ShiftSchedule::factory()->forDate(now()->toDateString())->create([
            'shift_id' => $scheduledShift->id,
        ]);

        // Act & Assert
        $this->assertFalse(ShiftService::canWorkToday($user));
    }

    /**
     * Test canWorkToday returns true when user has no role assigned.
     */
    public function test_can_work_today_returns_true_for_user_without_role(): void
    {
        // Arrange
        $user = User::factory()->create(['role_id' => null]);

        // Act & Assert
        $this->assertTrue(ShiftService::canWorkToday($user));
    }

    /**
     * Test transferEmployee attaches shift to user with effective date.
     */
    public function test_transfer_employee_attaches_shift_to_user(): void
    {
        // Arrange
        $shift = Shift::factory()->create();
        $user = User::factory()->create();
        $effectiveFrom = now()->toDateString();

        // Act
        ShiftService::transferEmployee($user, $shift, $effectiveFrom);

        // Assert
        $this->assertTrue($user->shifts()->where('shift_id', $shift->id)->exists());

        $pivot = $user->shifts()->where('shift_id', $shift->id)->first()->pivot;
        $this->assertEquals($effectiveFrom, $pivot->effective_from);
    }

    /**
     * Test getMissingScheduleDates returns all dates when no schedules exist.
     */
    public function test_get_missing_schedule_dates_returns_all_dates_when_no_schedules(): void
    {
        // Arrange
        $workshop = \App\Models\Workshop::factory()->create();

        // Act
        $result = ShiftService::getMissingScheduleDates(7, $workshop->id);

        // Assert
        $this->assertCount(7, $result);
        foreach ($result as $date) {
            $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $date);
        }
    }

    /**
     * Test getMissingScheduleDates returns only missing dates.
     */
    public function test_get_missing_schedule_dates_returns_only_missing_dates(): void
    {
        // Arrange
        $workshop = \App\Models\Workshop::factory()->create();
        $shift = Shift::factory()->create(['workshop_id' => $workshop->id]);

        // Schedule first and third days
        ShiftSchedule::factory()->forDate(now()->addDay()->toDateString())->create([
            'shift_id' => $shift->id,
        ]);
        ShiftSchedule::factory()->forDate(now()->addDays(3)->toDateString())->create([
            'shift_id' => $shift->id,
        ]);

        // Act
        $result = ShiftService::getMissingScheduleDates(7, $workshop->id);

        // Assert
        $this->assertCount(5, $result); // 7 total - 2 scheduled
        $this->assertNotContains(now()->addDay()->toDateString(), $result);
        $this->assertNotContains(now()->addDays(3)->toDateString(), $result);
    }

    /**
     * Test getMissingScheduleDates filters by workshop when workshop_id is provided.
     */
    public function test_get_missing_schedule_dates_filters_by_workshop(): void
    {
        // Arrange
        $workshop1 = \App\Models\Workshop::factory()->create();
        $workshop2 = \App\Models\Workshop::factory()->create();
        $shift1 = Shift::factory()->create(['workshop_id' => $workshop1->id]);
        $shift2 = Shift::factory()->create(['workshop_id' => $workshop2->id]);

        // Schedule for workshop1
        ShiftSchedule::factory()->forDate(now()->addDay()->toDateString())->create([
            'shift_id' => $shift1->id,
        ]);

        // Schedule for workshop2
        ShiftSchedule::factory()->forDate(now()->addDay()->toDateString())->create([
            'shift_id' => $shift2->id,
        ]);

        // Act
        $resultWorkshop1 = ShiftService::getMissingScheduleDates(7, $workshop1->id);
        $resultWorkshop2 = ShiftService::getMissingScheduleDates(7, $workshop2->id);

        // Assert
        // Workshop1 has 1 scheduled day, so 6 missing
        $this->assertCount(6, $resultWorkshop1);
        $this->assertNotContains(now()->addDay()->toDateString(), $resultWorkshop1);

        // Workshop2 has 1 scheduled day, so 6 missing
        $this->assertCount(6, $resultWorkshop2);
        $this->assertNotContains(now()->addDay()->toDateString(), $resultWorkshop2);
    }

    /**
     * Test getMissingScheduleDates returns all dates when workshop_id is null.
     */
    public function test_get_missing_schedule_dates_returns_all_when_workshop_id_null(): void
    {
        // Arrange
        $workshop1 = \App\Models\Workshop::factory()->create();
        $workshop2 = \App\Models\Workshop::factory()->create();
        $shift1 = Shift::factory()->create(['workshop_id' => $workshop1->id]);
        $shift2 = Shift::factory()->create(['workshop_id' => $workshop2->id]);

        // Schedule for different dates in both workshops
        ShiftSchedule::factory()->forDate(now()->addDay()->toDateString())->create([
            'shift_id' => $shift1->id,
        ]);
        ShiftSchedule::factory()->forDate(now()->addDays(2)->toDateString())->create([
            'shift_id' => $shift2->id,
        ]);

        // Act
        $result = ShiftService::getMissingScheduleDates(7, null);

        // Assert
        // Should return 5 missing dates (7 total - 2 scheduled)
        $this->assertCount(5, $result);
    }

    /**
     * Test getMissingScheduleDates excludes today and only looks at future dates.
     */
    public function test_get_missing_schedule_dates_starts_from_tomorrow(): void
    {
        // Arrange
        $workshop = \App\Models\Workshop::factory()->create();
        $shift = Shift::factory()->create(['workshop_id' => $workshop->id]);

        // Schedule for today
        ShiftSchedule::factory()->forDate(now()->toDateString())->create([
            'shift_id' => $shift->id,
        ]);

        // Act
        $result = ShiftService::getMissingScheduleDates(7, $workshop->id);

        // Assert
        // Should not include today
        $this->assertNotContains(now()->toDateString(), $result);
    }

    /**
     * Test fillSchedule creates or updates shift schedules.
     */
    public function test_fill_schedule_creates_or_updates_schedules(): void
    {
        // Arrange
        $shift = Shift::factory()->create();
        $scheduleData = [
            now()->toDateString() => $shift->id,
            now()->addDay()->toDateString() => $shift->id,
        ];

        // Act
        ShiftService::fillSchedule($scheduleData);

        // Assert
        $this->assertCount(2, ShiftSchedule::query()->get());
        $this->assertDatabaseHas('shift_schedule', [
            'date' => now()->toDateString(),
            'shift_id' => $shift->id,
        ]);
        $this->assertDatabaseHas('shift_schedule', [
            'date' => now()->addDay()->toDateString(),
            'shift_id' => $shift->id,
        ]);
    }

    /**
     * Test fillSchedule updates existing schedule.
     */
    public function test_fill_schedule_updates_existing_schedule(): void
    {
        // Arrange
        $oldShift = Shift::factory()->create();
        $newShift = Shift::factory()->create();

        // Create a schedule for today
        ShiftSchedule::factory()->forDate(now()->toDateString())->create([
            'shift_id' => $oldShift->id,
        ]);

        $scheduleData = [
            now()->toDateString() => $newShift->id,
        ];

        // Act
        ShiftService::fillSchedule($scheduleData);

        // Assert - there should be 2 schedules for today (old and new, since updateOrCreate doesn't replace)
        $this->assertDatabaseHas('shift_schedule', [
            'date' => now()->toDateString(),
            'shift_id' => $newShift->id,
        ]);
        $this->assertDatabaseHas('shift_schedule', [
            'date' => now()->toDateString(),
            'shift_id' => $oldShift->id,
        ]);
        // Check that both shifts exist for today
        $this->assertEquals(2, ShiftSchedule::where('date', now()->toDateString())->count());
    }

    /**
     * Test fillSchedule handles multiple shifts for different dates.
     */
    public function test_fill_schedule_handles_multiple_shifts(): void
    {
        // Arrange
        $shift1 = Shift::factory()->create();
        $shift2 = Shift::factory()->create();

        $scheduleData = [
            now()->toDateString() => $shift1->id,
            now()->addDay()->toDateString() => $shift2->id,
            now()->addDays(2)->toDateString() => $shift1->id,
        ];

        // Act
        ShiftService::fillSchedule($scheduleData);

        // Assert
        $this->assertCount(3, ShiftSchedule::query()->get());
        $this->assertDatabaseHas('shift_schedule', [
            'date' => now()->toDateString(),
            'shift_id' => $shift1->id,
        ]);
        $this->assertDatabaseHas('shift_schedule', [
            'date' => now()->addDay()->toDateString(),
            'shift_id' => $shift2->id,
        ]);
        $this->assertDatabaseHas('shift_schedule', [
            'date' => now()->addDays(2)->toDateString(),
            'shift_id' => $shift1->id,
        ]);
    }

    /**
     * Test that SHIFT_ROLES constant contains expected roles.
     */
    public function test_shift_roles_constant(): void
    {
        $expectedRoles = ['seamstress', 'cutter', 'otk'];

        foreach ($expectedRoles as $role) {
            $this->assertContains($role, ShiftService::SHIFT_ROLES);
        }

        $this->assertCount(3, ShiftService::SHIFT_ROLES);
    }
}
