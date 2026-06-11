<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Role;
use App\Models\Roll;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\TypeMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KioskRollShiftIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $storekeeper;

    private User $seamstress;

    private Shift $shiftA;

    private Shift $shiftB;

    private Roll $rollA;

    private Roll $rollB;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['name' => 'is_enabled_work_shift'], ['value' => '1']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $storekeeperRole = Role::firstOrCreate(['name' => 'storekeeper']);
        $seamstressRole = Role::firstOrCreate(['name' => 'seamstress']);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'shift_is_open' => true,
        ]);
        $this->storekeeper = User::factory()->create([
            'role_id' => $storekeeperRole->id,
            'shift_is_open' => true,
        ]);
        $this->seamstress = User::factory()->create([
            'role_id' => $seamstressRole->id,
            'shift_is_open' => true,
        ]);

        $this->shiftA = Shift::create(['name' => 'Смена А', 'status' => Shift::STATUS_ACTIVE]);
        $this->shiftB = Shift::create(['name' => 'Смена Б', 'status' => Shift::STATUS_ACTIVE]);

        // Привязываем швею к Смене А (effective_from = сегодня)
        $this->seamstress->shifts()->attach($this->shiftA->id, [
            'effective_from' => now()->toDateString(),
        ]);

        $typeMaterial = TypeMaterial::create(['title' => 'Ткань']);
        $material = Material::create([
            'title' => 'Тестовая ткань',
            'type_id' => $typeMaterial->id,
            'height' => 150,
            'unit' => 'м',
            'purchase_price' => 100,
        ]);

        $this->rollA = Roll::create([
            'shift_id' => $this->shiftA->id,
            'roll_code' => 'T-001',
            'material_id' => $material->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'initial_quantity' => 100,
        ]);

        $this->rollB = Roll::create([
            'shift_id' => $this->shiftB->id,
            'roll_code' => 'T-002',
            'material_id' => $material->id,
            'status' => Roll::STATUS_IN_WORKSHOP,
            'initial_quantity' => 80,
        ]);
    }

    /**
     * Имитирует вход в киоск: Laravel auth + session user_id.
     */
    private function actAsKioskUser(User $user): self
    {
        return $this->actingAs($user)
            ->withSession(['user_id' => $user->id]);
    }

    // ─── rolls() — поиск рулона по штрихкоду ───

    #[Test]
    public function seamstress_can_view_roll_of_own_shift(): void
    {
        $response = $this->actAsKioskUser($this->seamstress)
            ->get(route('kiosk.rolls', ['roll' => $this->rollA->roll_code]));

        $response->assertOk();
        $response->assertSee($this->rollA->roll_code);
    }

    #[Test]
    public function seamstress_cannot_view_roll_of_another_shift(): void
    {
        $response = $this->actAsKioskUser($this->seamstress)
            ->get(route('kiosk.rolls', ['roll' => $this->rollB->roll_code]));

        $response->assertRedirect(route('kiosk.rolls'));
        $response->assertSessionHas('error', 'Этот рулон принадлежит другой смене');
    }

    #[Test]
    public function admin_can_view_roll_of_any_shift(): void
    {
        $response = $this->actAsKioskUser($this->admin)
            ->get(route('kiosk.rolls', ['roll' => $this->rollB->roll_code]));

        $response->assertOk();
        $response->assertSee($this->rollB->roll_code);
    }

    #[Test]
    public function storekeeper_can_view_roll_of_any_shift(): void
    {
        $response = $this->actAsKioskUser($this->storekeeper)
            ->get(route('kiosk.rolls', ['roll' => $this->rollA->roll_code]));

        $response->assertOk();
        $response->assertSee($this->rollA->roll_code);
    }

    #[Test]
    public function user_without_shift_is_redirected_from_rolls(): void
    {
        $seamstressRole = Role::where('name', 'seamstress')->first();
        $orphan = User::factory()->create([
            'role_id' => $seamstressRole->id,
            'shift_is_open' => true,
        ]);

        $response = $this->actingAs($orphan)
            ->withSession(['user_id' => $orphan->id])
            ->get(route('kiosk.rolls', ['roll' => $this->rollA->roll_code]));

        $response->assertRedirect(route('kiosk'));
        $response->assertSessionHas('error', 'Вы не привязаны к смене. Обратитесь к администратору.');
    }

    #[Test]
    public function non_existent_roll_shows_not_found(): void
    {
        $response = $this->actAsKioskUser($this->seamstress)
            ->get(route('kiosk.rolls', ['roll' => 'NONEXISTENT']));

        $response->assertOk();
        $response->assertSee('Рулон не найден');
    }

    #[Test]
    public function rolls_page_shows_shift_name_in_roll_info(): void
    {
        $response = $this->actAsKioskUser($this->seamstress)
            ->get(route('kiosk.rolls', ['roll' => $this->rollA->roll_code]));

        $response->assertOk();
        $response->assertSee('Смена');
        $response->assertSee($this->shiftA->name);
    }

    // ─── completeRoll() — завершение рулона ───

    #[Test]
    public function seamstress_cannot_complete_roll_of_another_shift(): void
    {
        $response = $this->actAsKioskUser($this->seamstress)
            ->post(route('kiosk.complete-roll'), [
                'roll_id' => $this->rollB->id,
                'actual_remaining' => 10,
            ]);

        $response->assertRedirect(route('kiosk.rolls'));
        $response->assertSessionHas('error', 'Этот рулон принадлежит другой смене');

        // Рулон не должен был измениться
        $this->assertEquals(Roll::STATUS_IN_WORKSHOP, $this->rollB->fresh()->status);
    }

    #[Test]
    public function admin_can_complete_roll_of_any_shift(): void
    {
        $response = $this->actAsKioskUser($this->admin)
            ->post(route('kiosk.complete-roll'), [
                'roll_id' => $this->rollB->id,
                'actual_remaining' => 10,
            ]);

        $response->assertRedirect(route('kiosk.rolls'));
        $response->assertSessionHas('success');

        $this->assertEquals(Roll::STATUS_COMPLETED, $this->rollB->fresh()->status);
    }

    #[Test]
    public function storekeeper_can_complete_roll_of_any_shift(): void
    {
        $response = $this->actAsKioskUser($this->storekeeper)
            ->post(route('kiosk.complete-roll'), [
                'roll_id' => $this->rollB->id,
                'actual_remaining' => 10,
            ]);

        $response->assertRedirect(route('kiosk.rolls'));
        $response->assertSessionHas('success');

        $this->assertEquals(Roll::STATUS_COMPLETED, $this->rollB->fresh()->status);
    }

    #[Test]
    public function seamstress_can_complete_roll_of_own_shift(): void
    {
        $response = $this->actAsKioskUser($this->seamstress)
            ->post(route('kiosk.complete-roll'), [
                'roll_id' => $this->rollA->id,
                'actual_remaining' => 10,
            ]);

        $response->assertRedirect(route('kiosk.rolls'));
        $response->assertSessionHas('success');

        $this->assertEquals(Roll::STATUS_COMPLETED, $this->rollA->fresh()->status);
    }

    // ─── getRollByCode() — API-поиск ───

    #[Test]
    public function seamstress_can_find_roll_of_own_shift_via_api(): void
    {
        $response = $this->actAsKioskUser($this->seamstress)
            ->getJson(route('kiosk.api.roll', $this->rollA->roll_code));

        $response->assertOk();
        $response->assertJsonFragment(['material_id' => $this->rollA->material->title]);
    }

    #[Test]
    public function seamstress_cannot_find_roll_of_another_shift_via_api(): void
    {
        $response = $this->actAsKioskUser($this->seamstress)
            ->getJson(route('kiosk.api.roll', $this->rollB->roll_code));

        $response->assertOk();
        $response->assertJsonFragment(['material_id' => null]);
    }

    #[Test]
    public function admin_can_find_roll_of_any_shift_via_api(): void
    {
        $response = $this->actAsKioskUser($this->admin)
            ->getJson(route('kiosk.api.roll', $this->rollB->roll_code));

        $response->assertOk();
        $response->assertJsonFragment(['material_id' => $this->rollB->material->title]);
    }

    // ─── saveDefects() — сохранение брака ───

    #[Test]
    public function seamstress_cannot_save_defect_with_roll_of_another_shift(): void
    {
        $response = $this->actAsKioskUser($this->seamstress)
            ->post(route('defects.store'), [
                'user_id' => $this->seamstress->id,
                'roll' => $this->rollB->roll_code,
                'quantity' => 5,
                'reason' => 'Брак',
            ]);

        $response->assertRedirect(route('defects.create'));
        $response->assertSessionHas('error', 'Этот рулон принадлежит другой смене');
    }

    #[Test]
    public function seamstress_can_save_defect_with_roll_of_own_shift(): void
    {
        $response = $this->actAsKioskUser($this->seamstress)
            ->post(route('defects.store'), [
                'user_id' => $this->seamstress->id,
                'roll' => $this->rollA->roll_code,
                'quantity' => 5,
                'reason' => 'Брак',
            ]);

        // Успешное сохранение — отображение страницы дефектов (200, не redirect)
        $response->assertOk();
        $response->assertSessionMissing('error');
    }
}
