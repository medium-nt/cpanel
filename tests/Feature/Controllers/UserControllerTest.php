<?php

namespace Tests\Feature\Controllers;

use App\Models\Role;
use App\Models\User;
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
            'salary_rate' => 1500,
            'is_cutter' => false,
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'Пользователь добавлен');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role_id' => $role->id,
            'salary_rate' => 1500,
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
    public function user_salary_rate_is_stored_correctly()
    {
        $this->actingAs($this->admin);

        $userData = [
            'name' => 'Paid Worker',
            'email' => 'worker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => Role::firstOrCreate(['name' => 'seamstress'])->id,
            'salary_rate' => 1750.50,
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'salary_rate' => 1750.50,
        ]);
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
            'salary_rate' => 50.50,
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'cutter@example.com')->first();
        $this->assertEquals($cutterRole->id, $user->role_id);
        $this->assertTrue($user->isCutter());
    }
}
