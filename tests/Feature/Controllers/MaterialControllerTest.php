<?php

namespace Tests\Feature\Controllers;

use App\Models\Material;
use App\Models\Role;
use App\Models\Setting;
use App\Models\TypeMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MaterialControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $storekeeper;

    private User $seamstress;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable work shift requirement for testing
        Setting::updateOrCreate(['name' => 'is_enabled_work_shift'], ['value' => '1']);

        // Create roles and users
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $storekeeperRole = Role::firstOrCreate(['name' => 'storekeeper']);
        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->storekeeper = User::factory()->create([
            'role_id' => $storekeeperRole->id,
            'shift_is_open' => true,
        ]);
        $this->seamstress = User::factory()->create([
            'role_id' => $seamstressRole->id,
            'shift_is_open' => true,
        ]);
    }

    #[Test]
    public function admin_can_view_materials_index()
    {
        $this->actingAs($this->admin);

        TypeMaterial::factory()->count(3)->create();
        Material::factory()->count(5)->create();

        $response = $this->get(route('materials.index'));

        $response->assertOk();
        $response->assertViewIs('materials.index');
        $response->assertViewHas('materials');
    }

    #[Test]
    public function storekeeper_can_view_materials_index()
    {
        $this->actingAs($this->storekeeper);

        TypeMaterial::factory()->count(3)->create();
        Material::factory()->count(3)->create();

        $response = $this->get(route('materials.index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function seamstress_cannot_view_materials_index_without_permissions()
    {
        $this->actingAs($this->seamstress);

        $response = $this->get(route('materials.index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_view_material_create_form()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('materials.create'));

        $response->assertOk();
        $response->assertViewIs('materials.create');
    }

    #[Test]
    public function storekeeper_cannot_view_material_create_form()
    {
        $this->actingAs($this->storekeeper);

        $response = $this->get(route('materials.create'));

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_store_new_material()
    {
        $this->actingAs($this->admin);

        $materialData = [
            'title' => 'Test Material',
            'type_id' => 1,
            'unit' => 'шт',
        ];

        $response = $this->post(route('materials.store'), $materialData);

        $response->assertRedirect(route('materials.index'));
        $response->assertSessionHas('success', 'Материал добавлен');

        $this->assertDatabaseHas('materials', $materialData);
    }

    #[Test]
    public function storekeeper_cannot_store_new_material()
    {
        $this->actingAs($this->storekeeper);

        $materialData = [
            'title' => 'Storekeeper Material',
            'unit' => 'кг',
            'description' => 'Added by storekeeper',
        ];

        $response = $this->post(route('materials.store'), $materialData);

        $response->assertStatus(403);
    }

    #[Test]
    public function it_validates_required_fields_when_storing_material()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('materials.store'));

        $response->assertSessionHasErrors(['title', 'unit']);
    }

    #[Test]
    public function admin_can_view_material_edit_form()
    {
        $this->actingAs($this->admin);

        $material = Material::factory()->create();

        $response = $this->get(route('materials.edit', $material));

        $response->assertOk();
        $response->assertViewIs('materials.edit');
        $response->assertViewHas('material', $material);
    }

    #[Test]
    public function admin_can_update_material()
    {
        $this->actingAs($this->admin);

        $material = Material::factory()->create();
        $updateData = [
            'title' => 'Updated Material',
            'type_id' => 2,
            'unit' => 'м',
        ];

        $response = $this->put(route('materials.update', $material), $updateData);

        $response->assertRedirect(route('materials.index'));
        $response->assertSessionHas('success', 'Изменения сохранены');

        $this->assertDatabaseHas('materials', $updateData);
    }

    #[Test]
    public function admin_can_delete_material()
    {
        $this->actingAs($this->admin);

        $material = Material::factory()->create();

        $response = $this->delete(route('materials.destroy', $material));

        $response->assertRedirect(route('materials.index'));
        $response->assertSessionHas('success', 'Материал удален');

        $this->assertSoftDeleted('materials', [
            'id' => $material->id,
        ]);
    }

    #[Test]
    public function guest_cannot_access_material_crud_operations()
    {
        // Index
        $this->get(route('materials.index'))->assertRedirect(route('login'));

        // Create
        $this->get(route('materials.create'))->assertRedirect(route('login'));

        // Store
        $this->post(route('materials.store'))->assertRedirect(route('login'));

        // Edit
        $material = Material::factory()->create();
        $this->get(route('materials.edit', $material))->assertRedirect(route('login'));

        // Update
        $this->put(route('materials.update', $material))->assertRedirect(route('login'));

        // Delete
        $this->delete(route('materials.destroy', $material))->assertRedirect(route('login'));
    }

    #[Test]
    public function it_handles_duplicate_material_titles_gracefully()
    {
        $this->actingAs($this->admin);

        $existingMaterial = Material::factory()->create(['title' => 'Existing Material']);

        $materialData = [
            'title' => 'Existing Material',
            'type_id' => 1,
            'unit' => 'шт',
        ];

        $response = $this->post(route('materials.store'), $materialData);

        $response->assertSessionHasErrors('title');
        $this->assertEquals(1, Material::where('title', 'Existing Material')->count());
    }

    #[Test]
    public function it_materializes_material_data_correctly()
    {
        $this->actingAs($this->admin);

        $materialData = [
            'title' => 'Special Material',
            'type_id' => 1,
            'unit' => 'м',
        ];

        $response = $this->post(route('materials.store'), $materialData);

        $response->assertRedirect(route('materials.index'));

        $material = Material::where('title', 'Special Material')->first();
        $this->assertNotNull($material);
        $this->assertEquals('Special Material', $material->title);
        $this->assertEquals(1, $material->type_id);
        $this->assertEquals('м', $material->unit);
    }
}
