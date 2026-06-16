<?php

namespace Tests\Feature\Controllers;

use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Models\Workshop;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Create admin user
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    }

    #[Test]
    public function admin_can_view_users_index()
    {
        $this->actingAs($this->admin);

        // Create users with roles to avoid null role errors in view
        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);
        $cutterRole = Role::firstOrCreate(['name' => 'cutter']);
        $storekeeperRole = Role::firstOrCreate(['name' => 'storekeeper']);

        User::factory()->count(2)->create(['role_id' => $seamstressRole->id]);
        User::factory()->count(2)->create(['role_id' => $cutterRole->id]);
        User::factory()->count(1)->create(['role_id' => $storekeeperRole->id]);

        $response = $this->get(route('users.index'));

        $response->assertOk();
        $response->assertViewIs('users.index');
        $response->assertViewHas('users');
    }

    #[Test]
    public function admin_can_view_user_create_form()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('users.create'));

        $response->assertOk();
        $response->assertViewIs('users.create');
    }

    #[Test]
    public function admin_can_store_new_user()
    {
        $this->actingAs($this->admin);

        $role = Role::firstOrCreate(['name' => 'seamstress']);
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+71234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $role->id,
            'is_cutter' => false,
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'Пользователь добавлен');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role_id' => $role->id,
        ]);
    }

    #[Test]
    public function admin_can_view_user_edit_form()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $response = $this->get(route('users.edit', $user));

        $response->assertOk();
        $response->assertViewIs('users.edit');
        $response->assertViewHas('user', $user);
    }

    #[Test]
    public function admin_can_update_user()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'orders_priority' => 'all',
            'is_cutter' => true,
            'is_show_finance' => true,
        ];

        $response = $this->put(route('users.update', $user), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Изменения сохранены.');

        $this->assertDatabaseHas('users', $updateData);
    }

    #[Test]
    public function admin_can_delete_user()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $response = $this->delete(route('users.destroy', $user));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'Пользователь удален');

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    #[Test]
    public function guest_cannot_access_user_crud_operations()
    {
        // Index
        $this->get(route('users.index'))->assertRedirect(route('login'));

        // Create
        $this->get(route('users.create'))->assertRedirect(route('login'));

        // Store
        $this->post(route('users.store'))->assertRedirect(route('login'));

        // Edit
        $user = User::factory()->create();
        $this->get(route('users.edit', $user))->assertRedirect(route('login'));

        // Update
        $this->put(route('users.update', $user))->assertRedirect(route('login'));

        // Delete
        $this->delete(route('users.destroy', $user))->assertRedirect(route('login'));
    }

    #[Test]
    public function non_admin_user_cannot_access_user_management()
    {
        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);
        $seamstress = User::factory()->create(['role_id' => $seamstressRole->id]);

        $this->actingAs($seamstress);

        $response = $this->get(route('users.index'));
        $response->assertForbidden();
    }

    #[Test]
    public function it_validates_required_user_fields()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('users.store'));

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'role_id',
        ]);
    }

    #[Test]
    public function it_validates_unique_email()
    {
        $this->actingAs($this->admin);

        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => Role::firstOrCreate(['name' => 'seamstress'])->id,
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function it_validates_password_confirmation()
    {
        $this->actingAs($this->admin);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'role_id' => Role::firstOrCreate(['name' => 'seamstress'])->id,
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertSessionHasErrors(['password']);
    }

    #[Test]
    public function it_validates_email_format()
    {
        $this->actingAs($this->admin);

        $userData = [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => Role::firstOrCreate(['name' => 'seamstress'])->id,
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function user_role_is_correctly_assigned()
    {
        $this->actingAs($this->admin);

        $cutterRole = Role::firstOrCreate(['name' => 'cutter']);

        $userData = [
            'name' => 'Cutter User',
            'email' => 'cutter@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $cutterRole->id,
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'cutter@example.com')->first();
        $this->assertEquals($cutterRole->id, $user->role_id);
        $this->assertTrue($user->isCutter());
    }

    #[Test]
    public function it_filters_users_by_role()
    {
        $this->actingAs($this->admin);

        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);
        $cutterRole = Role::firstOrCreate(['name' => 'cutter']);

        User::factory()->create(['name' => 'Анна Швея', 'role_id' => $seamstressRole->id]);
        User::factory()->create(['name' => 'Борис Закройщик', 'role_id' => $cutterRole->id]);

        $response = $this->get(route('users.index', ['role_id' => $seamstressRole->id]));

        $response->assertOk();
        $response->assertSee('Анна Швея');
        $response->assertDontSee('Борис Закройщик');
    }

    #[Test]
    public function it_filters_users_by_current_workshop()
    {
        $this->actingAs($this->admin);

        $workshop1 = Workshop::factory()->create();
        $workshop2 = Workshop::factory()->create();
        $shift1 = Shift::factory()->create(['workshop_id' => $workshop1->id]);
        $shift2 = Shift::factory()->create(['workshop_id' => $workshop2->id]);

        $userInWorkshop1 = User::factory()->create(['name' => 'Виктор Вальфа']);
        $userInWorkshop1->shifts()->attach($shift1->id, ['effective_from' => now()->toDateString()]);

        $userInWorkshop2 = User::factory()->create(['name' => 'Григорий Гамма']);
        $userInWorkshop2->shifts()->attach($shift2->id, ['effective_from' => now()->toDateString()]);

        $response = $this->get(route('users.index', ['workshop_id' => $workshop1->id]));

        $response->assertOk();
        $response->assertSee('Виктор Вальфа');
        $response->assertDontSee('Григорий Гамма');
    }

    #[Test]
    public function it_filters_by_current_workshop_ignoring_older_shift()
    {
        $this->actingAs($this->admin);

        $workshop1 = Workshop::factory()->create();
        $workshop2 = Workshop::factory()->create();
        $shift1 = Shift::factory()->create(['workshop_id' => $workshop1->id]);
        $shift2 = Shift::factory()->create(['workshop_id' => $workshop2->id]);

        // Сотрудник работал вчера в цехе 1, сегодня переведён в цех 2 — текущий цех = 2.
        $transferredUser = User::factory()->create(['name' => 'Денис Переведенный']);
        $transferredUser->shifts()->attach($shift1->id, ['effective_from' => now()->subDay()->toDateString()]);
        $transferredUser->shifts()->attach($shift2->id, ['effective_from' => now()->toDateString()]);

        $this->get(route('users.index', ['workshop_id' => $workshop1->id]))
            ->assertDontSee('Денис Переведенный');

        $this->get(route('users.index', ['workshop_id' => $workshop2->id]))
            ->assertSee('Денис Переведенный');
    }

    #[Test]
    public function it_filters_users_by_role_and_workshop_combined()
    {
        $this->actingAs($this->admin);

        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);
        $cutterRole = Role::firstOrCreate(['name' => 'cutter']);

        $workshop1 = Workshop::factory()->create();
        $workshop2 = Workshop::factory()->create();
        $shift1 = Shift::factory()->create(['workshop_id' => $workshop1->id]);
        $shift2 = Shift::factory()->create(['workshop_id' => $workshop2->id]);

        // Швея в цехе 1 — единственный, кто должен попасть в выборку.
        $target = User::factory()->create(['name' => 'Елена Цель', 'role_id' => $seamstressRole->id]);
        $target->shifts()->attach($shift1->id, ['effective_from' => now()->toDateString()]);

        // Швея, но в другом цехе — не подходит.
        $otherWorkshop = User::factory()->create(['name' => 'Жанна ДругойЦех', 'role_id' => $seamstressRole->id]);
        $otherWorkshop->shifts()->attach($shift2->id, ['effective_from' => now()->toDateString()]);

        // Тот же цех, но другая роль — не подходит.
        $otherRole = User::factory()->create(['name' => 'Зина ДругаяРоль', 'role_id' => $cutterRole->id]);
        $otherRole->shifts()->attach($shift1->id, ['effective_from' => now()->toDateString()]);

        $response = $this->get(route('users.index', [
            'role_id' => $seamstressRole->id,
            'workshop_id' => $workshop1->id,
        ]));

        $response->assertOk();
        $response->assertSee('Елена Цель');
        $response->assertDontSee('Жанна ДругойЦех');
        $response->assertDontSee('Зина ДругаяРоль');
    }
}
