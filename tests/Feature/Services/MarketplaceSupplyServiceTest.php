<?php

namespace Tests\Feature\Services;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceSupply;
use App\Models\Setting;
use App\Services\MarketplaceSupplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceSupplyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ключи API читаются из таблицы настроек без null-safe проверок,
        // поэтому для сценариев с реальным вызовом API они должны существовать.
        // updateOrCreate устойчив к наличию записей (SettingsSeeder создаёт их в тестовой БД).
        Setting::updateOrCreate(['name' => 'seller_id_ozon'], ['value' => 'test-seller']);
        Setting::updateOrCreate(['name' => 'api_key_ozon'], ['value' => 'test-key']);
        Setting::updateOrCreate(['name' => 'api_key_wb'], ['value' => 'test-wb-key']);
    }

    /**
     * FBO-поставки со status=4 не должны обрабатываться: фильтр отсекает их
     * до любых HTTP-запросов к API маркетплейса.
     */
    #[Test]
    public function it_ignores_fbo_supplies_and_does_not_call_marketplace_api(): void
    {
        Http::fake();

        $supply = MarketplaceSupply::factory()->create([
            'marketplace_id' => 1,
            'type' => 'FBO',
            'status' => 4,
        ]);

        MarketplaceSupplyService::updateStatusSupply();

        // Фильтр отсекает FBO до любых HTTP-запросов к маркетплейсу.
        Http::assertNothingSent();

        $this->assertSame(4, $supply->fresh()->status);
        $this->assertNull($supply->fresh()->completed_at);
    }

    /**
     * FBS-поставка закрывается (status=3, completed_at), когда все её заказы
     * получили новые статусы, отсутствующие в списке «старых» статусов.
     */
    #[Test]
    public function it_processes_and_closes_fbs_supply_when_all_orders_got_new_statuses(): void
    {
        Http::fake([
            'api-seller.ozon.ru/*' => Http::response(['result' => ['status' => 'delivered']], 200),
        ]);

        $supply = MarketplaceSupply::factory()->create([
            'marketplace_id' => 1,
            'type' => 'FBS',
            'status' => 4,
        ]);

        $order = MarketplaceOrder::factory()->create([
            'marketplace_id' => 1,
            'supply_id' => $supply->id,
        ]);

        MarketplaceSupplyService::updateStatusSupply();

        $this->assertSame(3, $supply->fresh()->status);
        $this->assertNotNull($supply->fresh()->completed_at);
        $this->assertSame('delivered', $order->fresh()->marketplace_status);
    }

    /**
     * FBS-поставка остаётся открытой, если хотя бы у одного заказа сохраняется
     * «старый» статус (confirm / awaiting_deliver).
     */
    #[Test]
    public function it_keeps_fbs_supply_open_when_orders_still_have_old_statuses(): void
    {
        Http::fake([
            'api-seller.ozon.ru/*' => Http::response(['result' => ['status' => 'confirm']], 200),
        ]);

        $supply = MarketplaceSupply::factory()->create([
            'marketplace_id' => 1,
            'type' => 'FBS',
            'status' => 4,
        ]);

        MarketplaceOrder::factory()->create([
            'marketplace_id' => 1,
            'supply_id' => $supply->id,
        ]);

        MarketplaceSupplyService::updateStatusSupply();

        $this->assertSame(4, $supply->fresh()->status);
        $this->assertNull($supply->fresh()->completed_at);
    }
}
