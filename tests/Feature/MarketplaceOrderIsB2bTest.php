<?php

use App\Models\MarketplaceOrder;
use App\Models\Setting;
use App\Services\Marketplace\OzonApiService;
use App\Services\Marketplace\WbApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

// Helper function to setup API settings for tests
function setupApiSettings()
{
    Setting::query()->firstOrCreate(
        ['name' => 'api_key_wb'],
        ['value' => 'test-wb-key']
    );

    Setting::query()->firstOrCreate(
        ['name' => 'api_key_ozon'],
        ['value' => 'test-ozon-key']
    );

    Setting::query()->firstOrCreate(
        ['name' => 'seller_id_ozon'],
        ['value' => 'test-seller-id']
    );
}

test('ozon getAllNewOrders detects B2B order when legal_info.inn is present', function () {
    setupApiSettings();

    $mockResponse = [
        'result' => [
            'postings' => [
                [
                    'posting_number' => 'OZON-B2B-1',
                    'in_process_at' => '2026-07-15T10:00:00Z',
                    'products' => [
                        ['sku' => 'SKU001', 'quantity' => 1],
                    ],
                    'legal_info' => [
                        'company_name' => 'Test Company',
                        'inn' => '1234567890',
                        'kpp' => '',
                    ],
                ],
            ],
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = OzonApiService::getAllNewOrders();

    expect($result)->toBeArray();
    expect(count($result))->toBe(1);
    expect($result[0]->is_b2b)->toBeTrue();
});

test('ozon getAllNewOrders returns false for individual order when legal_info is empty', function () {
    setupApiSettings();

    $mockResponse = [
        'result' => [
            'postings' => [
                [
                    'posting_number' => 'OZON-B2C-1',
                    'in_process_at' => '2026-07-15T10:00:00Z',
                    'products' => [
                        ['sku' => 'SKU001', 'quantity' => 1],
                    ],
                    'legal_info' => [
                        'company_name' => '',
                        'inn' => '',
                        'kpp' => '',
                    ],
                ],
            ],
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = OzonApiService::getAllNewOrders();

    expect($result)->toBeArray();
    expect(count($result))->toBe(1);
    expect($result[0]->is_b2b)->toBeFalse();
});

test('wb getAllNewOrders detects B2B order when options.isB2B is true', function () {
    setupApiSettings();

    $mockResponse = [
        'orders' => [
            [
                'id' => 111,
                'createdAt' => '2026-07-15T10:00:00Z',
                'skus' => ['SKU1'],
                'options' => [
                    'isB2B' => true,
                ],
            ],
        ],
    ];

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/orders/new' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = WbApiService::getAllNewOrders();

    expect($result)->toBeArray();
    expect(count($result))->toBe(1);
    expect($result[0]->is_b2b)->toBeTrue();
});

test('wb getAllNewOrders returns false for individual order when options.isB2B is false', function () {
    setupApiSettings();

    $mockResponse = [
        'orders' => [
            [
                'id' => 222,
                'createdAt' => '2026-07-15T10:00:00Z',
                'skus' => ['SKU2'],
                'options' => [
                    'isB2B' => false,
                ],
            ],
        ],
    ];

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/orders/new' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = WbApiService::getAllNewOrders();

    expect($result)->toBeArray();
    expect(count($result))->toBe(1);
    expect($result[0]->is_b2b)->toBeFalse();
});

test('wb getAllNewOrders handles missing options field gracefully', function () {
    setupApiSettings();

    $mockResponse = [
        'orders' => [
            [
                'id' => 333,
                'createdAt' => '2026-07-15T10:00:00Z',
                'skus' => ['SKU3'],
                'options' => null,  // Null options instead of empty array
            ],
        ],
    ];

    Http::fake([
        'marketplace-api.wildberries.ru/*' => Http::response($mockResponse, 200),
    ]);

    $result = WbApiService::getAllNewOrders();

    expect($result)->toBeArray();
    expect(count($result))->toBe(1);
    expect($result[0]->is_b2b)->toBeFalse();
});

test('marketplaceOrder create with is_b2b flag stores as boolean in database and model', function () {
    $order = MarketplaceOrder::query()->create([
        'marketplace_id' => 1,
        'order_id' => 'TEST-B2B-123',
        'status' => 0,
        'is_b2b' => true,
    ]);

    expect($order->is_b2b)->toBeBool();
    expect($order->is_b2b)->toBeTrue();

    $this->assertDatabaseHas('marketplace_orders', [
        'order_id' => 'TEST-B2B-123',
        'is_b2b' => 1,
    ]);
});
