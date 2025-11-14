<?php

namespace Tests\Feature;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Role;
use App\Models\Sku;
use App\Models\User;
use App\Services\MarketplaceApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BarcodeGenerationTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);

        Storage::fake('public');
    }

    #[Test]
    public function it_generates_barcode_for_ozon_order()
    {
        $order = MarketplaceOrder::factory()->create([
            'order_id' => 'OZON-12345',
            'marketplace_id' => 1, // OZON
            'is_printed' => false,
            'status' => 0,
        ]);

        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('getBarcodeOzon')
            ->with($order->order_id)
            ->andReturn(new Response('PDF_BARCODE_CONTENT', 200, [
                'Content-Type' => 'application/pdf',
            ]));

        $this->app->instance(MarketplaceApiService::class, $mockService);

        $response = $this->get(route('marketplace_api.barcode', [
            'marketplaceOrderId' => $order->order_id,
        ]));

        $response->assertOk();
        $this->assertEquals('PDF_BARCODE_CONTENT', $response->getContent());

        // Verify order is marked as printed
        $order->refresh();
        $this->assertTrue((bool) $order->is_printed);
    }

    #[Test]
    public function it_generates_barcode_for_wb_order()
    {
        $order = MarketplaceOrder::factory()->create([
            'order_id' => '34567890',
            'marketplace_id' => 2, // WB
            'is_printed' => false,
            'status' => 0,
        ]);

        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('getBarcodeWb')
            ->with($order->order_id)
            ->andReturn(new Response('PDF_BARCODE_WB', 200, [
                'Content-Type' => 'application/pdf',
            ]));

        $this->app->instance(MarketplaceApiService::class, $mockService);

        $response = $this->get(route('marketplace_api.barcode', [
            'marketplaceOrderId' => $order->order_id,
        ]));

        $response->assertOk();
        $this->assertEquals('PDF_BARCODE_WB', $response->getContent());

        $order->refresh();
        $this->assertTrue((bool) $order->is_printed);
    }

    #[Test]
    public function it_generates_fbo_barcode()
    {
        Sku::factory()->create([
            'item_id' => 1,
            'sku' => 'FBO-11111',
            'marketplace_id' => 1,
        ]);

        $order = MarketplaceOrder::factory()->create([
            'order_id' => 'FBO-11111',
            'marketplace_id' => 1,
            'fulfillment_type' => 'fbo',
            'is_printed' => false,
            'status' => 0,
        ]);

        MarketplaceOrderItem::factory()->create([
            'marketplace_order_id' => $order->id,
            'marketplace_item_id' => 1,
        ]);

        $mockService = Mockery::mock(MarketplaceApiService::class);

        $mockService->shouldReceive('getBarcodeOzonFBO')
            ->with(Mockery::on(function ($arg) use ($order) {
                return $arg instanceof MarketplaceOrder && $arg->id === $order->id;
            }))
            ->andReturn(new Response('PDF_FBO_BARCODE', 200, [
                'Content-Type' => 'application/pdf',
            ]));

        $this->app->instance(MarketplaceApiService::class, $mockService);

        $response = $this->get(route('marketplace_api.fbo_barcode', [
            'marketplaceOrderId' => $order->order_id,
        ]));

        $response->assertOk();
        $this->assertEquals('PDF_FBO_BARCODE', $response->getContent());

        $order->refresh();
        $this->assertTrue((bool) $order->is_printed);
    }

    #[Test]
    public function it_handles_nonexistent_order_gracefully()
    {
        $response = $this->get(route('marketplace_api.barcode'), [
            'marketplaceOrderId' => 'NONEXISTENT-999',
        ]);

        $response->assertSee('Нет заказа с таким номером!');
    }

    #[Test]
    public function it_handles_unknown_marketplace_gracefully()
    {
        $order = MarketplaceOrder::factory()->create([
            'order_id' => 'UNKNOWN-123',
            'marketplace_id' => 99, // Unknown marketplace
            'is_printed' => false,
        ]);

        $response = $this->get(route('marketplace_api.barcode'), [
            'marketplaceOrderId' => 'UNKNOWN-123',
        ]);

        $response->assertOk();
        $response->assertSee('Нет заказа с таким номером!');

        // Order should still be marked as printed
        $order->refresh();
        $this->assertFalse((bool) $order->is_printed);
    }

    #[Test]
    public function it_marks_order_as_printed_only_once()
    {
        $order = MarketplaceOrder::factory()->create([
            'order_id' => 'PRINT-TEST',
            'marketplace_id' => 1,
            'is_printed' => false,
            'status' => 0,
        ]);

        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('getBarcodeOzon')
            ->with($order->order_id)
            ->andReturn(new Response('PRINT-TEST', 200, [
                'Content-Type' => 'application/pdf',
            ]));

        $this->app->instance(MarketplaceApiService::class, $mockService);

        // First request
        $response1 = $this->get(route('marketplace_api.barcode', [
            'marketplaceOrderId' => $order->order_id,
        ]));
        $response1->assertOk();

        $order->refresh();
        $this->assertTrue((bool) $order->is_printed);

        // Second request should still work (though order already printed)
        $response2 = $this->get(route('marketplace_api.barcode', [
            'marketplaceOrderId' => $order->order_id,
        ]));
        $response2->assertOk();

        // Should still be marked as printed
        $order->refresh();
        $this->assertTrue((bool) $order->is_printed);
    }

    #[Test]
    public function it_handles_missing_marketplace_order_id_parameter()
    {
        $response = $this->get(route('marketplace_api.barcode'));

        $response->assertOk();
        $response->assertSee('Нет заказа с таким номером!');
    }

    #[Test]
    public function it_handles_empty_marketplace_order_id()
    {
        $response = $this->get(route('marketplace_api.barcode', [
            'marketplaceOrderId' => '',
        ]));

        $response->assertOk();
    }

    #[Test]
    public function it_concurrent_requests_for_same_order()
    {
        $order = MarketplaceOrder::factory()->create([
            'order_id' => 'CONCURRENT-123',
            'marketplace_id' => 1,
            'is_printed' => false,
        ]);

        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('getBarcodeOzon')
            ->with('CONCURRENT-123')
            ->andReturn(new Response('CONCURRENT-123', 200, [
                'Content-Type' => 'application/pdf',
            ]))
            ->times(2); // Expect 2 calls

        $this->app->instance(MarketplaceApiService::class, $mockService);

        // Simulate concurrent requests
        $response1 = $this->get(route('marketplace_api.barcode', [
            'marketplaceOrderId' => $order->order_id,
        ]));
        $response1->assertOk();

        $response2 = $this->get(route('marketplace_api.barcode', [
            'marketplaceOrderId' => $order->order_id,
        ]));
        $response2->assertOk();

        $order->refresh();
        $this->assertTrue((bool) $order->is_printed);
    }

    #[Test]
    public function it_handles_special_characters_in_order_id()
    {
        $specialOrderIds = [
            'ORDER-测试-123',
            'ORDER_123_ABC',
            'ORDER@123#ABC',
            'ORDER/123\\ABC',
        ];

        foreach ($specialOrderIds as $orderId) {
            $order = MarketplaceOrder::factory()->create([
                'order_id' => $orderId,
                'marketplace_id' => 1,
            ]);

            $mockService = Mockery::mock(MarketplaceApiService::class);
            $mockService->shouldReceive('getBarcodeOzon')
                ->with($orderId)
                ->andReturn(new Response('BARCODE_SPECIAL', 200, [
                    'Content-Type' => 'application/pdf',
                ]));

            $this->app->instance(MarketplaceApiService::class, $mockService);

            $response = $this->get(route('marketplace_api.barcode', [
                'marketplaceOrderId' => $orderId,
            ]));

            $response->assertOk();
            $this->assertEquals('BARCODE_SPECIAL', $response->getContent());
        }
    }

    #[Test]
    public function barcode_endpoint_accessible_without_authentication()
    {
        // Test that barcode endpoints bypass authentication middleware
        $response = $this->get(route('marketplace_api.barcode'), [
            'marketplaceOrderId' => 'AUTH-TEST',
        ]);

        $response->assertOk();
    }
}
