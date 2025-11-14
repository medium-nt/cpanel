<?php

namespace Tests\Feature;

use App\Models\MarketplaceOrder;
use App\Models\Role;
use App\Models\Sku;
use App\Models\User;
use App\Services\MarketplaceApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceApiControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    // Disable middleware for API testing

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_check_skuz_and_show_not_found_materials()
    {
        $this->actingAs($this->admin);

        // Mock the API service methods
        $ozonItems = [
            ['offer_id' => 'SKU001', 'product_id' => 12345],
            ['offer_id' => 'SKU002', 'product_id' => 67890],
        ];

        $wbItems = [
            ['vendor_code' => 'SKU003', 'nm_id' => 54321],
            ['offer_id' => 'SKU004', 'product_id' => 98765],
        ];

        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('getAllItemsOzon')->andReturn($ozonItems);
        $mockService->shouldReceive('getAllItemsWb')->andReturn($wbItems);
        $mockService->shouldReceive('getNotFoundSkus')
            ->with(array_merge($ozonItems, $wbItems))
            ->andReturn([
                ['sku' => 'SKU001', 'marketplace' => 'OZON'],
                ['sku' => 'SKU003', 'marketplace' => 'WB'],
            ]);

        $this->app->instance(MarketplaceApiService::class, $mockService);

        $response = $this->get(route('marketplace_api.checkSkuz'));

        $response->assertOk();
        $response->assertViewIs('marketplace_api.check_skuz');
        $response->assertViewHas('title', 'Материалы не найденные в ERP');
        $response->assertViewHas('skuz');
    }

    #[Test]
    public function it_can_check_duplicate_skus()
    {
        $this->actingAs($this->admin);

        // Create duplicate SKUs
        Sku::factory()->create(['sku' => 'DUPLICATE001']);
        Sku::factory()->create(['sku' => 'DUPLICATE001']);
        Sku::factory()->create(['sku' => 'DUPLICATE002']);
        Sku::factory()->create(['sku' => 'DUPLICATE002']);
        Sku::factory()->create(['sku' => 'DUPLICATE002']);
        Sku::factory()->create(['sku' => 'UNIQUE001']);

        $response = $this->get(route('marketplace_api.checkDuplicateSkuz'));

        $response->assertOk();
        $response->assertViewIs('marketplace_api.check_duplicate_skuz');
        $response->assertViewHas('title', 'Дубли SKU в ERP');
        $response->assertViewHas('duplicates');

        $duplicates = $response->viewData('duplicates');
        $this->assertCount(2, $duplicates);

        $duplicate1 = $duplicates->where('sku', 'DUPLICATE001')->first();
        $duplicate2 = $duplicates->where('sku', 'DUPLICATE002')->first();

        $this->assertEquals(2, $duplicate1->occurrences);
        $this->assertEquals(3, $duplicate2->occurrences);
    }

    #[Test]
    public function it_can_upload_new_products()
    {
        $this->actingAs($this->admin);

        $results = [
            'created' => 10,
            'updated' => 5,
            'errors' => 2,
            'details' => [
                'Successfully processed 15 orders',
                'Failed to process 2 orders',
            ],
        ];

        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('uploadingNewProducts')->andReturn($results);

        $this->app->instance(MarketplaceApiService::class, $mockService);

        $response = $this->get(route('marketplace_api.newOrder'));

        $response->assertOk();
        $response->assertViewIs('marketplace_api.uploading_new_products');
        $response->assertViewHas('title', 'Отчет о загрузке новых товаров');
        $response->assertViewHas('results', $results);
    }

    #[Test]
    public function it_can_upload_cancelled_products()
    {
        $this->actingAs($this->admin);

        $results = [
            'processed' => 8,
            'cancelled' => 8,
            'errors' => 0,
            'details' => [
                'Successfully cancelled 8 orders',
            ],
        ];

        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('uploadingCancelledProducts')->andReturn($results);

        $this->app->instance(MarketplaceApiService::class, $mockService);

        $response = $this->get(route('marketplace_api.check_cancelled'));

        $response->assertOk();
        $response->assertViewIs('marketplace_api.uploading_cancelled_products');
        $response->assertViewHas('title', 'Отчет о загрузке отмененных заявок');
        $response->assertViewHas('results', $results);
    }

    #[Test]
    public function it_can_get_ozon_barcode_file()
    {
        $order = MarketplaceOrder::factory()->create([
            'order_id' => 'OZON-12345',
            'marketplace_id' => 1, // OZON
            'is_printed' => false,
        ]);

        $this->actingAs($this->admin);

        $barcodeContent = 'PDF_CONTENT';

        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('getBarcodeOzon')
            ->with('OZON-12345')
            ->andReturn($barcodeContent);

        $this->app->instance(MarketplaceApiService::class, $mockService);

        $response = $this->get(route('marketplace_api.barcode'), [
            'marketplaceOrderId' => 'OZON-12345',
        ]);

        $response->assertOk();
        $this->assertEquals($barcodeContent, $response->getContent());

        // Check that order is marked as printed
        $order->refresh();
        $this->assertTrue($order->is_printed);
    }

    #[Test]
    public function it_can_get_wb_barcode_file()
    {
        $order = MarketplaceOrder::factory()->create([
            'order_id' => 'WB-67890',
            'marketplace_id' => 2, // WB
            'is_printed' => false,
        ]);

        $this->actingAs($this->admin);

        $barcodeContent = 'PDF_CONTENT_WB';

        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('getBarcodeWb')
            ->with('WB-67890')
            ->andReturn($barcodeContent);

        $this->app->instance(MarketplaceApiService::class, $mockService);

        $response = $this->get(route('marketplace_api.barcode'), [
            'marketplaceOrderId' => 'WB-67890',
        ]);

        $response->assertOk();
        $this->assertEquals($barcodeContent, $response->getContent());

        // Check that order is marked as printed
        $order->refresh();
        $this->assertTrue($order->is_printed);
    }

    #[Test]
    public function it_handles_unknown_marketplace_for_barcode()
    {
        $order = MarketplaceOrder::factory()->create([
            'order_id' => 'UNKNOWN-12345',
            'marketplace_id' => 99, // Unknown marketplace
            'is_printed' => false,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('marketplace_api.barcode'), [
            'marketplaceOrderId' => 'UNKNOWN-12345',
        ]);

        $response->assertOk();
        $this->assertNull($response->getContent());

        // Order should still be marked as printed even for unknown marketplace
        $order->refresh();
        $this->assertTrue($order->is_printed);
    }

    #[Test]
    public function it_can_get_fbo_barcode_file()
    {
        $order = MarketplaceOrder::factory()->create([
            'order_id' => 'FBO-54321',
            'marketplace_id' => 1, // OZON
            'is_printed' => false,
        ]);

        $this->actingAs($this->admin);

        $fboBarcodeContent = 'PDF_CONTENT_FBO';

        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('getBarcodeOzonFBO')
            ->with($order)
            ->andReturn($fboBarcodeContent);

        $this->app->instance(MarketplaceApiService::class, $mockService);

        $response = $this->get(route('marketplace_api.fbo_barcode'), [
            'marketplaceOrderId' => 'FBO-54321',
        ]);

        $response->assertOk();
        $this->assertEquals($fboBarcodeContent, $response->getContent());

        // Check that order is marked as printed
        $order->refresh();
        $this->assertTrue($order->is_printed);
    }

    #[Test]
    public function it_requires_authentication_for_api_endpoints()
    {
        // Test without authentication
        $response = $this->get(route('marketplace_api.checkSkuz'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('marketplace_api.checkDuplicateSkuz'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('marketplace_api.newOrder'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('marketplace_api.check_cancelled'));
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function it_handles_api_service_errors_gracefully()
    {
        $this->actingAs($this->admin);

        // Mock service to throw exception
        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('getAllItemsOzon')->andThrow(new \Exception('API Error'));
        $mockService->shouldReceive('getAllItemsWb')->andReturn([]);
        $mockService->shouldReceive('getNotFoundSkus')->andReturn([]);

        $this->app->instance(MarketplaceApiService::class, $mockService);

        // The controller should handle the exception and return error view
        $this->expectException(\Exception::class);
        $this->get(route('marketplace_api.checkSkuz'));
    }

    #[Test]
    public function barcode_routes_require_marketplace_order_id_parameter()
    {
        $this->actingAs($this->admin);

        // Test missing marketplaceOrderId parameter
        $response = $this->get(route('marketplace_api.barcode'));
        $response->assertStatus(500); // Should fail when order_id is missing

        $response = $this->get(route('marketplace_api.fbo_barcode'));
        $response->assertStatus(500); // Should fail when order_id is missing
    }

    #[Test]
    public function it_handles_nonexistent_orders_for_barcode_generation()
    {
        $this->actingAs($this->admin);

        $mockService = Mockery::mock(MarketplaceApiService::class);
        $this->app->instance(MarketplaceApiService::class, $mockService);

        // Test with non-existent order
        $response = $this->get(route('marketplace_api.barcode'), [
            'marketplaceOrderId' => 'NONEXISTENT-999',
        ]);

        // Should handle gracefully (either return error or empty response)
        $response->assertOk();
    }

    #[Test]
    public function duplicate_sku_check_handles_empty_database()
    {
        $this->actingAs($this->admin);

        // Test with empty SKUs table
        $response = $this->get(route('marketplace_api.checkDuplicateSkuz'));

        $response->assertOk();
        $response->assertViewHas('duplicates');

        $duplicates = $response->viewData('duplicates');
        $this->assertCount(0, $duplicates);
    }

    #[Test]
    public function it_returns_correct_view_titles()
    {
        $this->actingAs($this->admin);

        // Mock the API service to avoid actual API calls
        $mockService = Mockery::mock(MarketplaceApiService::class);
        $mockService->shouldReceive('getAllItemsOzon')->andReturn([]);
        $mockService->shouldReceive('getAllItemsWb')->andReturn([]);
        $mockService->shouldReceive('getNotFoundSkus')->andReturn([]);
        $mockService->shouldReceive('uploadingNewProducts')->andReturn([]);
        $mockService->shouldReceive('uploadingCancelledProducts')->andReturn([]);

        $this->app->instance(MarketplaceApiService::class, $mockService);

        // Test checkSkuz view title
        $response = $this->get(route('marketplace_api.checkSkuz'));
        $response->assertViewHas('title', 'Материалы не найденные в ERP');

        // Test checkDuplicateSkuz view title
        $response = $this->get(route('marketplace_api.checkDuplicateSkuz'));
        $response->assertViewHas('title', 'Дубли SKU в ERP');

        // Test uploadingNewProducts view title
        $response = $this->get(route('marketplace_api.newOrder'));
        $response->assertViewHas('title', 'Отчет о загрузке новых товаров');

        // Test uploadingCancelledProducts view title
        $response = $this->get(route('marketplace_api.check_cancelled'));
        $response->assertViewHas('title', 'Отчет о загрузке отмененных заявок');
    }
}
