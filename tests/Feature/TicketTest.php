<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('redirects guest to login on create page', function () {
    $response = $this->get(route('tickets.create'));

    $response->assertRedirect(route('login'));
});

it('creates ticket by authenticated employee', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('tickets.store'), [
            'description' => 'Test ticket description',
        ]);

    $this->assertDatabaseHas('tickets', [
        'user_id' => $user->id,
        'description' => 'Test ticket description',
        'status' => Ticket::STATUS_NEW,
    ]);

    $ticket = Ticket::where('user_id', $user->id)->first();
    $response->assertRedirect(route('tickets.show', $ticket));
});

it('creates ticket with screenshot', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('screenshot.jpg');

    $this->actingAs($user)
        ->post(route('tickets.store'), [
            'description' => 'Ticket with screenshot',
            'screenshot' => $file,
        ]);

    $ticket = Ticket::where('user_id', $user->id)->first();

    expect($ticket->screenshot)->not->toBeNull()
        ->and($ticket->screenshot)->toContain('tickets/');

    Storage::disk('public')->assertExists($ticket->screenshot);
});

it('pre-fills page_url on create from query parameter', function () {
    $user = User::factory()->create();
    $testUrl = 'http://site.test/page';

    $response = $this->actingAs($user)
        ->get(route('tickets.create', ['url' => $testUrl]));

    $response->assertSuccessful()
        ->assertViewHas('pageUrl', $testUrl);
});

it('shows only own new tickets to employee on index scope=new', function () {
    $employee = User::factory()->create();
    $otherUser = User::factory()->create();

    Ticket::factory()->create(['user_id' => $employee->id, 'status' => Ticket::STATUS_NEW]);
    Ticket::factory()->create(['user_id' => $employee->id, 'status' => Ticket::STATUS_CLOSED]);
    Ticket::factory()->create(['user_id' => $employee->id, 'status' => Ticket::STATUS_DELETED]);
    Ticket::factory()->create(['user_id' => $otherUser->id, 'status' => Ticket::STATUS_NEW]);

    $response = $this->actingAs($employee)
        ->get(route('tickets.index', ['scope' => 'new']));

    $response->assertSuccessful()
        ->assertViewHas('tickets', function ($tickets) use ($employee) {
            return $tickets->pluck('user_id')->every(fn ($id) => $id === $employee->id)
                && $tickets->every(fn ($t) => $t->status === Ticket::STATUS_NEW);
        });
});

it('shows only own processed tickets to employee on index scope=processed', function () {
    $employee = User::factory()->create();

    Ticket::factory()->for($employee)->closed()->create();
    Ticket::factory()->for($employee)->deleted()->create();
    Ticket::factory()->for($employee)->create(['status' => Ticket::STATUS_NEW]);

    $response = $this->actingAs($employee)
        ->get(route('tickets.index', ['scope' => 'processed']));

    $response->assertSuccessful()
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->every(fn ($t) => in_array($t->status, [Ticket::STATUS_CLOSED, Ticket::STATUS_DELETED]))
                && $tickets->count() === 2;
        });
});

it('shows all tickets to admin on index', function () {
    $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Ticket::factory()->for($user1)->create(['status' => Ticket::STATUS_NEW]);
    Ticket::factory()->for($user2)->create(['status' => Ticket::STATUS_NEW]);
    Ticket::factory()->for($user1)->closed()->create();

    $response = $this->actingAs($admin)
        ->get(route('tickets.index', ['scope' => 'new']));

    $response->assertSuccessful()
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 2
                && $tickets->every(fn ($t) => $t->status === Ticket::STATUS_NEW);
        });
});

it('forbids employee to view other user ticket', function () {
    $employee = User::factory()->create();
    $otherUser = User::factory()->create();
    $ticket = Ticket::factory()->for($otherUser)->create();

    $response = $this->actingAs($employee)
        ->get(route('tickets.show', $ticket));

    $response->assertForbidden();
});

it('forbids employee to close ticket', function () {
    $employee = User::factory()->create();
    $ticket = Ticket::factory()->for($employee)->inProgress()->create();

    $response = $this->actingAs($employee)
        ->put(route('tickets.close', $ticket), [
            'admin_comment' => 'Should not work',
        ]);

    $response->assertForbidden();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_IN_PROGRESS);
});

it('allows admin to close ticket', function () {
    $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $ticket = Ticket::factory()->inProgress()->create();

    $this->actingAs($admin)
        ->put(route('tickets.close', $ticket), [
            'admin_comment' => 'Ticket successfully resolved',
        ])
        ->assertRedirect(route('tickets.index', ['scope' => 'new']));

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_CLOSED)
        ->and($ticket->fresh()->closed_at)->not->toBeNull()
        ->and($ticket->fresh()->admin_comment)->toBe('Ticket successfully resolved');
});

it('forbids employee to delete ticket', function () {
    $employee = User::factory()->create();
    $ticket = Ticket::factory()->for($employee)->create();

    $response = $this->actingAs($employee)
        ->put(route('tickets.delete', $ticket));

    $response->assertForbidden();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_NEW);
});

it('allows admin to delete ticket', function () {
    $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $ticket = Ticket::factory()->create();

    $this->actingAs($admin)
        ->put(route('tickets.delete', $ticket))
        ->assertRedirect(route('tickets.index', ['scope' => 'new']));

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_DELETED);
});

it('shows badge count for new tickets on admin menu', function () {
    $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    Ticket::factory()->count(3)->create(['status' => Ticket::STATUS_NEW]);
    Ticket::factory()->closed()->create();

    $this->actingAs($admin)
        ->get(route('tickets.index'))
        ->assertSuccessful();

    expect(Ticket::where('status', Ticket::STATUS_NEW)->count())->toBe(3);
});

it('forbids admin to close already closed ticket', function () {
    $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $ticket = Ticket::factory()->closed()->create();

    $this->actingAs($admin)
        ->put(route('tickets.close', $ticket), [
            'admin_comment' => 'Should not work',
        ])
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_CLOSED);
});

it('forbids admin to close new ticket without starting it', function () {
    $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $ticket = Ticket::factory()->create();

    $this->actingAs($admin)
        ->put(route('tickets.close', $ticket), [
            'admin_comment' => 'Trying to close without starting',
        ])
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_NEW)
        ->and($ticket->fresh()->admin_comment)->toBeNull();
});

it('rejects non-image screenshot upload', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post(route('tickets.store'), [
            'description' => 'Описание проблемы',
            'screenshot' => $file,
        ])
        ->assertSessionHasErrors('screenshot');

    expect(Ticket::count())->toBe(0);
});

it('forbids employee to start ticket', function () {
    $employee = User::factory()->create();
    $ticket = Ticket::factory()->for($employee)->create();

    $response = $this->actingAs($employee)
        ->put(route('tickets.start', $ticket));

    $response->assertForbidden();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_NEW);
});

it('allows admin to start ticket', function () {
    $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $ticket = Ticket::factory()->create();

    $this->actingAs($admin)
        ->put(route('tickets.start', $ticket))
        ->assertRedirect(route('tickets.index', ['scope' => 'new']));

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_IN_PROGRESS);
});

it('forbids admin to start already closed ticket', function () {
    $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $ticket = Ticket::factory()->closed()->create();

    $this->actingAs($admin)
        ->put(route('tickets.start', $ticket))
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_CLOSED);
});

it('allows admin to close ticket in progress', function () {
    $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $ticket = Ticket::factory()->inProgress()->create();

    $this->actingAs($admin)
        ->put(route('tickets.close', $ticket), [
            'admin_comment' => 'Fixed and tested',
        ])
        ->assertRedirect(route('tickets.index', ['scope' => 'new']));

    expect($ticket->fresh()->status)->toBe(Ticket::STATUS_CLOSED)
        ->and($ticket->fresh()->closed_at)->not->toBeNull()
        ->and($ticket->fresh()->admin_comment)->toBe('Fixed and tested');
});

it('stores admin comment when closing ticket', function () {
    $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $ticket = Ticket::factory()->inProgress()->create();

    $this->actingAs($admin)
        ->put(route('tickets.close', $ticket), [
            'admin_comment' => 'TestCommentUnique123 - Fixed the button issue',
        ])
        ->assertRedirect(route('tickets.index', ['scope' => 'new']));

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'status' => Ticket::STATUS_CLOSED,
        'admin_comment' => 'TestCommentUnique123 - Fixed the button issue',
    ]);

    expect($ticket->fresh()->closed_at)->not->toBeNull();
});

it('shows new and in progress tickets in scope new', function () {
    $employee = User::factory()->create();

    Ticket::factory()->for($employee)->create(['status' => Ticket::STATUS_NEW]);
    Ticket::factory()->for($employee)->inProgress()->create();
    Ticket::factory()->for($employee)->closed()->create();

    $response = $this->actingAs($employee)
        ->get(route('tickets.index', ['scope' => 'new']));

    $response->assertSuccessful()
        ->assertViewHas('tickets', function ($tickets) {
            return $tickets->count() === 2
                && $tickets->contains(fn ($t) => $t->status === Ticket::STATUS_NEW)
                && $tickets->contains(fn ($t) => $t->status === Ticket::STATUS_IN_PROGRESS);
        });
});

it('shows admin comment to ticket author', function () {
    $employee = User::factory()->create();
    $ticket = Ticket::factory()->for($employee)->create([
        'admin_comment' => 'TestCommentUnique123 - Administrator response here',
    ]);

    $this->actingAs($employee)
        ->get(route('tickets.show', $ticket))
        ->assertSuccessful()
        ->assertSee('TestCommentUnique123');
});
