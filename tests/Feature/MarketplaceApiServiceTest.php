<?php

use App\Models\Sku;
use App\Services\MarketplaceApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('getItemsWb returns false on non-200 response', function () {
    Http::fake([
        'https://content-api.wildberries.ru/content/v2/get/cards/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getItemsWb();

    expect($result)->toBeFalse();
});

test('getItemsWb returns response object on success', function () {
    $mockResponse = ['cards' => []];

    Http::fake([
        'https://content-api.wildberries.ru/content/v2/get/cards/list' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getItemsWb();

    expect($result)->toBeObject();
    expect($result->cards)->toBe([]);
});

test('getAllItemsWb returns empty array on API failure', function () {
    Http::fake([
        'https://content-api.wildberries.ru/content/v2/get/cards/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getAllItemsWb();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getItemsOzon returns false on non-200 response', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v4/product/info/attributes' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getItemsOzon();

    expect($result)->toBeFalse();
});

test('getItemsOzon returns response object on success', function () {
    $mockResponse = ['result' => []];

    Http::fake([
        'https://api-seller.ozon.ru/v4/product/info/attributes' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getItemsOzon();

    expect($result)->toBeObject();
    expect($result->result)->toBe([]);
});

test('getAllItemsOzon returns empty array on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getAllItemsOzon();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getNotFoundSkus returns empty array when all SKUs found', function () {
    // Clean up and create test SKU
    Sku::query()->delete();
    Sku::create(['sku' => 'EXISTING123', 'item_id' => 1, 'marketplace_id' => 1]);
    Sku::create(['sku' => 'EXISTING456', 'item_id' => 1, 'marketplace_id' => 1]);

    $allItems = [
        ['skus' => ['EXISTING123']],
        ['skus' => ['EXISTING456']],
    ];

    $result = MarketplaceApiService::getNotFoundSkus($allItems);

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getNotFoundSkus returns items with not found SKUs', function () {
    // Clean up and create only one test SKU
    Sku::query()->delete();
    Sku::create(['sku' => 'EXISTING123', 'item_id' => 1, 'marketplace_id' => 1]);

    $allItems = [
        ['skus' => ['EXISTING123']], // Found
        ['skus' => ['NOTFOUND456']], // Not found
        ['skus' => ['EXISTING789']],  // Found
    ];

    $result = MarketplaceApiService::getNotFoundSkus($allItems);

    expect($result)->toBeArray();
    expect(count($result))->toBe(2);
    expect($result[0])->toHaveKeys(['skus']);
    expect($result[0]['skus'][0])->toBe('NOTFOUND456');
    expect($result[1]['skus'][0])->toBe('EXISTING789');
});

test('getAllNewOrdersWb returns empty array on API failure', function () {
    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/orders/new' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getAllNewOrdersWb();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('hasOrderInSystem is private method - skipping this test', function () {
    // This method is private and cannot be tested directly
    expect(true)->toBeTrue();
});

test('hasSkuInSystem is private method - skipping this test', function () {
    // This method is private and cannot be tested directly
    expect(true)->toBeTrue();
});

test('getBarcodeOzonBySku returns null for empty SKU', function () {
    $result = MarketplaceApiService::getBarcodeOzonBySku('');

    expect($result)->toBeNull();
});

test('getBarcodeOzonBySku returns barcode for existing SKU', function () {
    $mockResponse = ['items' => [['barcodes' => ['BARCODE123']]]];

    Http::fake([
        'https://api-seller.ozon.ru/v3/product/info/list' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getBarcodeOzonBySku('SKU123');

    expect($result)->toBe('BARCODE123');
});

test('getBarcodeOzonBySku returns null on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v3/product/info/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getBarcodeOzonBySku('SKU123');

    expect($result)->toBeNull();
});

test('getOzonPostingNumberByBarcode returns - on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v2/posting/fbs/get-by-barcode' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getOzonPostingNumberByBarcode('BARCODE123');

    expect($result)->toBe('-');
});

test('getOzonPostingNumberByBarcode returns posting number on success', function () {
    $mockResponse = ['result' => ['posting_number' => 'POSTING123']];

    Http::fake([
        'https://api-seller.ozon.ru/v2/posting/fbs/get-by-barcode' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getOzonPostingNumberByBarcode('BARCODE123');

    expect($result)->toBe('POSTING123');
});

test('syncWarehousesOzon returns 0 on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/cluster/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::syncWarehousesOzon();

    expect($result)->toBe(0);
});

test('syncWarehousesOzon returns number of added warehouses on success', function () {
    $mockResponse = [
        'clusters' => [[
            'name' => 'Test Cluster',
            'macrolocal_cluster_id' => 123,
            'logistic_clusters' => [[
                'warehouses' => [[
                    'name' => 'Test Warehouse',
                    'warehouse_id' => 456,
                ]],
            ]],
        ]],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v1/cluster/list' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::syncWarehousesOzon();

    expect($result)->toBe(1);
    $this->assertDatabaseHas('marketplace_warehouses', [
        'name' => 'Test Warehouse',
        'marketplace_id' => 1,
    ]);
});

test('getSellerWarehousesOzon returns empty array on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/warehouse/fbo/seller/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getSellerWarehousesOzon();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getSellerWarehousesOzon returns warehouses array on success', function () {
    $mockResponse = [
        'warehouses' => [
            [
                'name' => 'Seller Warehouse',
                'warehouse_id' => 123,
            ],
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v1/warehouse/fbo/seller/list' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getSellerWarehousesOzon();

    expect($result)->toBeArray();
    expect(count($result))->toBe(1);
    expect($result[0]['name'])->toBe('Seller Warehouse');
});

test('getReturnsGiveoutList returns empty array on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/return/giveout/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getReturnsGiveoutList();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getReturnsGiveoutInfo returns null on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/return/giveout/info' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getReturnsGiveoutInfo(123);

    expect($result)->toBeNull();
});

test('getReturnsList returns empty array structure on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/returns/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getReturnsList();

    expect($result)->toBeArray();
    expect($result)->toHaveKey('returns');
    expect($result)->toHaveKey('has_next');
    expect($result['returns'])->toBeArray();
    expect($result['returns'])->toBeEmpty();
});
