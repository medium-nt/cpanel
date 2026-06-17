<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KioskCleanerAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $cleaner;

    private Workshop $workshop;

    protected function setUp(): void
    {
        parent::setUp();

        $cleanerRole = Role::firstOrCreate(['name' => 'cleaner']);

        // Уборщица не привязана к смене/цеху (currentWorkshop() = null).
        // Смена уже открыта — чтобы убедиться, что операционный функционал
        // киоска скрыт именно из-за роли cleaner, а не из-за закрытой смены.
        $this->cleaner = User::factory()->create([
            'role_id' => $cleanerRole->id,
            'shift_is_open' => true,
        ]);

        $this->workshop = Workshop::factory()->create();
    }

    #[Test]
    public function cleaner_can_enter_kiosk_of_any_workshop_by_barcode(): void
    {
        $barcode = '1-'.$this->cleaner->id.'-1';

        $response = $this->actingAs($this->cleaner)
            ->withSession(['kiosk_workshop_id' => $this->workshop->id])
            ->get(route('kiosk', ['barcode' => $barcode]));

        $response->assertOk();
        $response->assertViewIs('kiosk.kiosk');
        $response->assertDontSee('Вы не принадлежите к цеху');
    }

    #[Test]
    public function cleaner_sees_only_shift_button_in_kiosk(): void
    {
        $response = $this->actingAs($this->cleaner)
            ->withSession([
                'kiosk_workshop_id' => $this->workshop->id,
                'user_id' => $this->cleaner->id,
            ])
            ->get(route('kiosk'));

        $response->assertOk();
        // Кнопка учёта рабочего времени доступна.
        $response->assertSee('Открытие');
        // Операционный функционал киоска скрыт даже при открытой смене.
        $response->assertDontSee('Печать заказов');
        $response->assertDontSee('Работа с рулонами');
        $response->assertDontSee('Печать стикеров товара');
        $response->assertDontSee('Статистика');
    }
}
