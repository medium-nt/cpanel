<?php

namespace Tests\Feature\Services;

use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use updateOrCreate to prevent unique constraint violations
        Setting::updateOrCreate(['name' => 'is_enabled_work_schedule'], ['value' => '1']);
        Setting::updateOrCreate(['name' => 'working_day_start'], ['value' => '09:00']);
        Setting::updateOrCreate(['name' => 'working_day_end'], ['value' => '18:00']);
    }

    public function test_get_schedule_by_user_id(): void
    {
        // Arrange
        $user = User::factory()->create();
        Schedule::factory()->count(3)->create(['user_id' => $user->id]);

        // Act
        $schedule = ScheduleService::getScheduleByUserId($user->id);

        // Assert
        $this->assertCount(3, $schedule);
        $this->assertArrayHasKey('start', $schedule[0]);
        $this->assertArrayHasKey('display', $schedule[0]);
        $this->assertEquals('background', $schedule[0]['display']);
    }

    public function test_is_work_day_returns_true_if_schedule_exists(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        Carbon::setTestNow(now());
        Schedule::factory()->create(['user_id' => $user->id, 'date' => now()->toDateString()]);

        // Act & Assert
        $this->assertTrue(ScheduleService::isWorkDay());
    }

    public function test_is_work_day_returns_false_if_schedule_does_not_exist(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);
        Carbon::setTestNow(now());

        // Act & Assert
        $this->assertFalse(ScheduleService::isWorkDay());
    }

    public function test_is_enabled_schedule_returns_correct_value(): void
    {
        // Assert that the initial value from setUp is read correctly
        $this->assertTrue(ScheduleService::isEnabledSchedule());

        // Update the value and assert the change is detected
        Setting::query()->where('name', 'is_enabled_work_schedule')->update(['value' => '0']);
        $this->assertFalse(ScheduleService::isEnabledSchedule());
    }

    public function test_get_start_and_end_work_day(): void
    {
        // Act & Assert
        $this->assertEquals('09:00', ScheduleService::getStartWorkDay());
        $this->assertEquals('18:00', ScheduleService::getEndWorkDay());
    }

    public function test_has_work_day_started(): void
    {
        // Arrange & Act & Assert
        Carbon::setTestNow(Carbon::createFromTimeString('08:59:59'));
        $this->assertFalse(ScheduleService::hasWorkDayStarted());

        Carbon::setTestNow(Carbon::createFromTimeString('09:00:00'));
        $this->assertTrue(ScheduleService::hasWorkDayStarted());

        Carbon::setTestNow(Carbon::createFromTimeString('17:59:59'));
        $this->assertTrue(ScheduleService::hasWorkDayStarted());

        Carbon::setTestNow(Carbon::createFromTimeString('18:00:00'));
        $this->assertFalse(ScheduleService::hasWorkDayStarted());
    }

    public function test_is_before_start_work_day(): void
    {
        $user = User::factory()->create([
            'shift_is_open' => 0,
            'closed_work_shift' => '00:00:00',
        ]);
        $this->actingAs($user);

        // Проверяем что смена не открыта и время закрытия равно '00:00:00' (не открывалась)
        $this->assertTrue(ScheduleService::isBeforeStartWorkDay($user));

        // Проверяем что смена открыта и время закрытия равно '00:00:00' (не открывалась)
        $user->shift_is_open = 1;
        $user->save();
        $this->assertFalse(ScheduleService::isBeforeStartWorkDay($user));

        // Проверяем что смена открыта и время закрытия не равно '00:00:00' (открылась)
        $user->closed_work_shift = '09:00:00';
        $user->save();
        $this->assertFalse(ScheduleService::isBeforeStartWorkDay($user));

        // Проверяем что смена не открыта и время закрытия не равно '00:00:00' (открылась)
        $user->shift_is_open = 0;
        $user->save();
        $this->assertFalse(ScheduleService::isBeforeStartWorkDay($user));
    }
}
