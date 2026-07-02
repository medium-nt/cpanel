<?php

namespace Tests\Feature\Controllers;

use App\Models\Material;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MaterialVisibilityInFormsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable work shift requirement
        Setting::updateOrCreate(['name' => 'is_enabled_work_shift'], ['value' => '1']);

        // Create admin role and user
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    }

    #[Test]
    public function archived_and_unorderable_materials_excluded_from_movements_to_workshop_create()
    {
        $this->actingAs($this->admin);

        // Create three materials with different statuses
        $activeMaterial = Material::factory()->create([
            'title' => 'Active Material',
            'is_active' => true,
            'is_archive' => false,
        ]);

        $unorderableMaterial = Material::factory()->create([
            'title' => 'Unorderable Material',
            'is_active' => false,
            'is_archive' => false,
        ]);

        $archivedMaterial = Material::factory()->create([
            'title' => 'Archived Material',
            'is_active' => false,
            'is_archive' => true,
        ]);

        $response = $this->get(route('movements_to_workshop.create'));

        $response->assertOk();

        // Get materials from the view
        $materials = $response->viewData('materials');
        $materialIds = collect($materials)->pluck('id');

        // Active material should be present
        $this->assertContains($activeMaterial->id, $materialIds);

        // Unorderable material should NOT be present (uses Material::active())
        $this->assertNotContains($unorderableMaterial->id, $materialIds);

        // Archived material should NOT be present
        $this->assertNotContains($archivedMaterial->id, $materialIds);
    }

    #[Test]
    public function archived_excluded_but_unorderable_visible_in_write_off_remnants_create()
    {
        $this->actingAs($this->admin);

        // Create three materials with different statuses
        $activeMaterial = Material::factory()->create([
            'title' => 'Active WriteOff Material',
            'is_active' => true,
            'is_archive' => false,
        ]);

        $unorderableMaterial = Material::factory()->create([
            'title' => 'Unorderable WriteOff Material',
            'is_active' => false,
            'is_archive' => false,
        ]);

        $archivedMaterial = Material::factory()->create([
            'title' => 'Archived WriteOff Material',
            'is_active' => false,
            'is_archive' => true,
        ]);

        $response = $this->get(route('write_off_remnants.create'));

        $response->assertOk();

        // Get materials from the view
        $materials = $response->viewData('materials');
        $materialIds = collect($materials)->pluck('id');

        // Active material should be present
        $this->assertContains($activeMaterial->id, $materialIds);

        // Unorderable material should be present (uses Material::notArchived())
        $this->assertContains($unorderableMaterial->id, $materialIds);

        // Archived material should NOT be present
        $this->assertNotContains($archivedMaterial->id, $materialIds);
    }

    #[Test]
    public function archived_and_unorderable_excluded_from_marketplace_items_create()
    {
        $this->actingAs($this->admin);

        // Create three materials with different statuses
        $activeMaterial = Material::factory()->create([
            'title' => 'Active Marketplace Material',
            'is_active' => true,
            'is_archive' => false,
        ]);

        $unorderableMaterial = Material::factory()->create([
            'title' => 'Unorderable Marketplace Material',
            'is_active' => false,
            'is_archive' => false,
        ]);

        $archivedMaterial = Material::factory()->create([
            'title' => 'Archived Marketplace Material',
            'is_active' => false,
            'is_archive' => true,
        ]);

        $response = $this->get(route('marketplace_items.create'));

        $response->assertOk();

        // Get materials from the view
        $materials = $response->viewData('materials');
        $materialIds = collect($materials)->pluck('id');

        // Active material should be present
        $this->assertContains($activeMaterial->id, $materialIds);

        // Unorderable material should NOT be present (uses Material::active())
        $this->assertNotContains($unorderableMaterial->id, $materialIds);

        // Archived material should NOT be present
        $this->assertNotContains($archivedMaterial->id, $materialIds);
    }
}
