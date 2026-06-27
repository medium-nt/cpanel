<?php

use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed roles before each test
    $this->seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);
});

test('current_users_returns_employee_assigned_to_shift', function () {
    // Arrange: Create workshop, shift, and user
    $workshop = Workshop::factory()->create();
    $shift = Shift::factory()->create([
        'workshop_id' => $workshop->id,
        'status' => 'active',
    ]);

    $user = User::factory()->create(['role_id' => $this->seamstressRole->id]);

    // Attach user to shift with yesterday's effective date
    $user->shifts()->attach($shift->id, [
        'effective_from' => today()->subDay()->toDateString(),
    ]);

    // Assert: getCurrentUsers() returns 1 employee
    expect($shift->getCurrentUsers()->count())->toBe(1);

    // Assert: currentUsers() relation returns 1 employee
    expect($shift->currentUsers()->count())->toBe(1);

    // Assert: withCount loads users_count correctly
    $shiftWithCount = Shift::withCount(['currentUsers as users_count'])
        ->find($shift->id);

    expect($shiftWithCount->users_count)->toBe(1);
});

test('current_users_excludes_employee_transferred_to_another_shift', function () {
    // Arrange: Create workshop with 2 shifts and 1 user
    $workshop = Workshop::factory()->create();
    $shiftA = Shift::factory()->create([
        'workshop_id' => $workshop->id,
        'status' => 'active',
    ]);
    $shiftB = Shift::factory()->create([
        'workshop_id' => $workshop->id,
        'status' => 'active',
    ]);

    $user = User::factory()->create(['role_id' => $this->seamstressRole->id]);

    // Attach user to shift A with 3 days ago date
    $user->shifts()->attach($shiftA->id, [
        'effective_from' => today()->subDays(3)->toDateString(),
    ]);

    // Assert: User is counted in shift A initially
    $shiftA->refresh();
    expect($shiftA->currentUsers()->count())->toBe(1);
    expect($shiftB->currentUsers()->count())->toBe(0);

    // Act: Transfer user to shift B with yesterday's date (more recent than shift A attachment)
    // This simulates a real transfer: user moved from A to B yesterday
    $user->shifts()->attach($shiftB->id, [
        'effective_from' => today()->subDay()->toDateString(),
    ]);

    // Assert: After transfer, shift A has 0, shift B has 1
    $shiftA->refresh();
    $shiftB->refresh();

    expect($shiftA->currentUsers()->count())->toBe(0);
    expect($shiftB->currentUsers()->count())->toBe(1);

    // Assert: withCount works correctly for both shifts
    $shiftsWithCount = Shift::withCount(['currentUsers as users_count'])
        ->whereIn('id', [$shiftA->id, $shiftB->id])
        ->get()
        ->keyBy('id');

    expect($shiftsWithCount[$shiftA->id]->users_count)->toBe(0);
    expect($shiftsWithCount[$shiftB->id]->users_count)->toBe(1);

    // Assert: Total count for workshop is 1, not 2
    $workshop->load(['shifts' => function ($query) {
        $query->withCount(['currentUsers as users_count']);
    }]);

    $totalUsers = $workshop->shifts->sum('users_count');
    expect($totalUsers)->toBe(1);
});

test('current_users_includes_employee_in_old_shift_until_future_transfer_date', function () {
    // Arrange: Create workshop with 2 shifts and 1 user
    $workshop = Workshop::factory()->create();
    $shiftA = Shift::factory()->create([
        'workshop_id' => $workshop->id,
        'status' => 'active',
    ]);
    $shiftB = Shift::factory()->create([
        'workshop_id' => $workshop->id,
        'status' => 'active',
    ]);

    $user = User::factory()->create(['role_id' => $this->seamstressRole->id]);

    // Attach user to shift A with yesterday's date
    $user->shifts()->attach($shiftA->id, [
        'effective_from' => today()->subDay()->toDateString(),
    ]);

    // Act: Transfer user to shift B with TOMORROW's date (future transfer)
    $user->shifts()->attach($shiftB->id, [
        'effective_from' => today()->addDay()->toDateString(),
    ]);

    // Assert: User is still counted in shift A (current), not in shift B (incoming)
    $shiftA->refresh();
    $shiftB->refresh();

    expect($shiftA->currentUsers()->count())->toBe(1);
    expect($shiftB->currentUsers()->count())->toBe(0);

    // Assert: withCount reflects the same
    $shiftsWithCount = Shift::withCount(['currentUsers as users_count'])
        ->whereIn('id', [$shiftA->id, $shiftB->id])
        ->get()
        ->keyBy('id');

    expect($shiftsWithCount[$shiftA->id]->users_count)->toBe(1);
    expect($shiftsWithCount[$shiftB->id]->users_count)->toBe(0);
});

test('current_users_excludes_future_pivot_in_same_shift', function () {
    // Arrange: Create workshop, shift, and user
    $workshop = Workshop::factory()->create();
    $shift = Shift::factory()->create([
        'workshop_id' => $workshop->id,
        'status' => 'active',
    ]);

    $user = User::factory()->create(['role_id' => $this->seamstressRole->id]);

    // Attach user to shift with yesterday's date
    $user->shifts()->attach($shift->id, [
        'effective_from' => today()->subDay()->toDateString(),
    ]);

    // Act: Add another pivot record with tomorrow's date (edge case)
    $user->shifts()->attach($shift->id, [
        'effective_from' => today()->addDay()->toDateString(),
    ]);

    // Assert: Only 1 current user (future pivot is ignored)
    $shift->refresh();
    expect($shift->currentUsers()->count())->toBe(1);

    // Assert: withCount loads correctly (not inflated by future pivot)
    $shiftWithCount = Shift::withCount(['currentUsers as users_count'])
        ->find($shift->id);

    expect($shiftWithCount->users_count)->toBe(1);

    // Assert: Total pivot records = 2, but current users = 1
    $totalPivots = DB::table('shift_user')
        ->where('shift_id', $shift->id)
        ->where('user_id', $user->id)
        ->count();

    expect($totalPivots)->toBe(2);
    expect($shift->currentUsers()->count())->toBe(1);
});
