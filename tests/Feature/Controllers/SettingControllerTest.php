<?php

namespace Tests\Feature\Controllers;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable work shift requirement for testing
        Setting::updateOrCreate(['name' => 'is_enabled_work_shift'], ['value' => '1']);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    }

    /**
     * Страница настроек должна показывать глобальное значение,
     * даже если есть цеховое переопределение с тем же name.
     */
    #[Test]
    public function index_shows_global_value_when_workshop_override_exists(): void
    {
        $workshop = Workshop::factory()->create();
        Setting::factory()->create(['name' => 'timeout_200', 'value' => '2', 'workshop_id' => null]);
        Setting::factory()->create(['name' => 'timeout_200', 'value' => '1', 'workshop_id' => $workshop->id]);

        $this->actingAs($this->admin);
        $response = $this->get(route('setting.index'));

        $response->assertOk();

        $settings = $response->viewData('settings');
        $this->assertSame('2', $settings->timeout_200, 'Должно показываться глобальное значение, а не цеховое.');
    }

    /**
     * Сохранение должно обновлять только глобальную настройку,
     * оставляя цеховые переопределения нетронутыми.
     */
    #[Test]
    public function save_updates_only_global_setting_and_keeps_workshop_override(): void
    {
        $workshop = Workshop::factory()->create();
        Setting::factory()->create(['name' => 'timeout_200', 'value' => '2', 'workshop_id' => null]);
        Setting::factory()->create(['name' => 'timeout_200', 'value' => '1', 'workshop_id' => $workshop->id]);

        $this->actingAs($this->admin);
        $response = $this->post(route('setting.save'), [
            'working_day_start' => '07:00',
            'working_day_end' => '20:00',
            'is_enabled_work_schedule' => '0',
            'timeout_200' => '9',
        ]);

        $response->assertRedirect(route('setting.index'));

        $this->assertSame(
            '9',
            Setting::query()->where('name', 'timeout_200')->whereNull('workshop_id')->value('value'),
            'Глобальная настройка должна обновиться.'
        );
        $this->assertSame(
            '1',
            Setting::query()->where('name', 'timeout_200')->where('workshop_id', $workshop->id)->value('value'),
            'Цеховое переопределение не должно измениться.'
        );
    }
}
