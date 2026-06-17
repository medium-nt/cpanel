<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Роли с ограниченным доступом в киоск: входят в киоск любого цеха,
 * но видят только кнопку «Открытие / Закрытие смены» (учёт времени).
 */
class KioskLimitedAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Роли, которым разрешён вход в любой цех только для отметки смены.
     *
     * @return array<string, array{0: string}>
     */
    public static function limitedRoles(): array
    {
        return [
            'cleaner' => ['cleaner'],
            'driver' => ['driver'],
        ];
    }

    /**
     * Создать пользователя ограниченной роли со открытой сменой.
     */
    private function createLimitedUser(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $role])->id,
            'shift_is_open' => true,
        ]);
    }

    #[Test]
    #[DataProvider('limitedRoles')]
    public function limited_role_can_enter_kiosk_of_any_workshop_by_barcode(string $role): void
    {
        $user = $this->createLimitedUser($role);
        $workshop = Workshop::factory()->create();
        $barcode = '1-'.$user->id.'-1';

        $response = $this->actingAs($user)
            ->withSession(['kiosk_workshop_id' => $workshop->id])
            ->get(route('kiosk', ['barcode' => $barcode]));

        $response->assertOk();
        $response->assertViewIs('kiosk.kiosk');
        $response->assertDontSee('Вы не принадлежите к цеху');
    }

    #[Test]
    #[DataProvider('limitedRoles')]
    public function limited_role_sees_only_shift_button_in_kiosk(string $role): void
    {
        $user = $this->createLimitedUser($role);
        $workshop = Workshop::factory()->create();

        $response = $this->actingAs($user)
            ->withSession([
                'kiosk_workshop_id' => $workshop->id,
                'user_id' => $user->id,
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
