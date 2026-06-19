<?php

namespace Tests\Feature\Controllers;

use App\Models\Material;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MaterialSupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $storekeeper;

    private Material $material;

    private Supplier $supplier1;

    private Supplier $supplier2;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['name' => 'is_enabled_work_shift'], ['value' => '1']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $storekeeperRole = Role::firstOrCreate(['name' => 'storekeeper']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->storekeeper = User::factory()->create([
            'role_id' => $storekeeperRole->id,
            'shift_is_open' => true,
        ]);

        $this->material = Material::factory()->create();
        $this->supplier1 = Supplier::factory()->create();
        $this->supplier2 = Supplier::factory()->create();
    }

    #[Test]
    public function admin_can_attach_supplier_to_material()
    {
        $this->actingAs($this->admin);

        $response = $this->post(
            route('materials.suppliers.attach', $this->material),
            ['supplier_id' => $this->supplier1->id]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('material_supplier', [
            'material_id' => $this->material->id,
            'supplier_id' => $this->supplier1->id,
            'shortage_percent' => 0,
        ]);
    }

    #[Test]
    public function attaching_already_attached_supplier_fails_validation()
    {
        $this->actingAs($this->admin);
        $this->material->suppliers()->attach($this->supplier1->id);

        $response = $this->post(
            route('materials.suppliers.attach', $this->material),
            ['supplier_id' => $this->supplier1->id]
        );

        $response->assertSessionHasErrors();
        $this->assertEquals(1, $this->material->suppliers()->count());
    }

    #[Test]
    public function admin_can_update_shortage_percent()
    {
        $this->actingAs($this->admin);

        $this->material->suppliers()->attach($this->supplier1->id, ['shortage_percent' => 0]);
        $pivot = $this->material->suppliers()->first()->pivot;

        $response = $this->put(
            route('materials.suppliers.update', $this->material),
            ['shortages' => [$pivot->id => 15.5]]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('material_supplier', [
            'id' => $pivot->id,
            'shortage_percent' => 15.5,
        ]);
    }

    #[Test]
    public function update_shortages_guarded_to_material()
    {
        $this->actingAs($this->admin);

        $material2 = Material::factory()->create();
        $material2->suppliers()->attach($this->supplier1->id, ['shortage_percent' => 0]);
        $this->material->suppliers()->attach($this->supplier2->id, ['shortage_percent' => 0]);

        $pivotFromMaterial2 = $material2->suppliers()->first()->pivot;

        $response = $this->put(
            route('materials.suppliers.update', $this->material),
            ['shortages' => [$pivotFromMaterial2->id => 99.99]]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Проверяем, что процентmaterial2 НЕ изменился (защита where material_id)
        $this->assertDatabaseHas('material_supplier', [
            'id' => $pivotFromMaterial2->id,
            'shortage_percent' => 0,
        ]);
    }

    #[Test]
    public function admin_can_detach_supplier_from_material()
    {
        $this->actingAs($this->admin);

        $this->material->suppliers()->attach($this->supplier1->id);
        $pivot = $this->material->suppliers()->first()->pivot;

        $response = $this->delete(
            route('materials.suppliers.detach', ['material' => $this->material->id, 'pivotId' => $pivot->id])
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('material_supplier', [
            'id' => $pivot->id,
        ]);
    }

    #[Test]
    public function non_admin_gets_403()
    {
        $this->actingAs($this->storekeeper);

        $response = $this->post(
            route('materials.suppliers.attach', $this->material),
            ['supplier_id' => $this->supplier1->id]
        );

        $response->assertStatus(403);
    }

    #[Test]
    public function guest_redirected_to_login()
    {
        $this->material->suppliers()->attach($this->supplier1->id);
        $pivot = $this->material->suppliers()->first()->pivot;

        $this->post(route('materials.suppliers.attach', $this->material))
            ->assertRedirect(route('login'));

        $this->put(route('materials.suppliers.update', $this->material))
            ->assertRedirect(route('login'));

        $this->delete(route('materials.suppliers.detach', ['material' => $this->material->id, 'pivotId' => $pivot->id]))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function attaching_supplier_logs_to_materials_channel()
    {
        $this->actingAs($this->admin);

        Log::shouldReceive('channel')->once()->with('materials')->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('Привязан поставщик к материалу', Mockery::on(function ($context) {
                return $context['material_id'] === $this->material->id
                    && $context['supplier_id'] === $this->supplier1->id
                    && $context['attached_by'] === $this->admin->id;
            }));

        $this->post(
            route('materials.suppliers.attach', $this->material),
            ['supplier_id' => $this->supplier1->id]
        );
    }
}
