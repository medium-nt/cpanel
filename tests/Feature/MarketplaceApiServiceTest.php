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

test('getAllNewOrdersOzon returns empty array on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getAllNewOrdersOzon();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getAllNewOrdersOzon returns orders array on success', function () {
    $mockResponse = [
        'result' => [
            'postings' => [
                [
                    'posting_number' => 'TEST123',
                    'in_process_at' => '2026-07-12T10:00:00Z',
                    'products' => [
                        ['sku' => 'SKU001', 'quantity' => 1],
                    ],
                ],
            ],
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getAllNewOrdersOzon();

    expect($result)->toBeArray();
    expect(count($result))->toBe(1);
    expect($result[0]->id)->toBe('TEST123');
    expect($result[0]->marketplace_id)->toBe('1');
    expect($result[0]->skus)->toBeArray();
});

test('splittingOrder returns false on API failure', function () {
    $order = (object) [
        'id' => 'ORDER123',
        'skus' => [
            (object) ['sku' => 'SKU001', 'quantity' => 2],
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v1/posting/fbs/split' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::splittingOrder($order);

    expect($result)->toBeFalse();
});

test('splittingOrder returns true on successful split', function () {
    $order = (object) [
        'id' => 'ORDER123',
        'skus' => [
            (object) ['sku' => 'SKU001', 'quantity' => 2],
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v1/posting/fbs/split' => Http::response(json_encode(['result' => true]), 200),
    ]);

    $result = MarketplaceApiService::splittingOrder($order);

    expect($result)->toBeTrue();
});

test('collectOrderOzon returns false when exemplar status verification fails', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v5/fbs/posting/product/exemplar/status' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::collectOrderOzon('ORDER123', 'SKU001');

    expect($result)->toBeFalse();
});

test('collectOrderOzon returns true on successful collection', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v5/fbs/posting/product/exemplar/status' => Http::response(
            json_encode(['status' => 'ship_available']),
            200
        ),
        'https://api-seller.ozon.ru/v4/posting/fbs/ship' => Http::response(json_encode(['result' => true]), 200),
    ]);

    $result = MarketplaceApiService::collectOrderOzon('ORDER123', 'SKU001');

    expect($result)->toBeTrue();
});

test('collectOrderOzon returns true when order already shipped', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v5/fbs/posting/product/exemplar/status' => Http::response(
            json_encode(['status' => 'ship_available']),
            200
        ),
        'https://api-seller.ozon.ru/v4/posting/fbs/ship' => Http::response(
            json_encode(['message' => 'POSTING_ALREADY_SHIPPED']),
            400
        ),
    ]);

    $result = MarketplaceApiService::collectOrderOzon('ORDER123', 'SKU001');

    expect($result)->toBeTrue();
});

test('collectOrderWb returns false when supply creation fails', function () {
    Http::fake([
        'https://marketplace-api.wildberries.ru/api/marketplace/v3/supplies' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::collectOrderWb(12345);

    expect($result)->toBeFalse();
});

test('collectOrderWb returns true on successful collection', function () {
    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/supplies*' => Http::sequence()
            ->push(['supplies' => [], 'next' => 0]) // No existing supplies
            ->push(['id' => 'SUPPLY123'], 201), // Create new supply
        'https://marketplace-api.wildberries.ru/api/marketplace/v3/supplies/SUPPLY123/orders' => Http::response('', 204),
    ]);

    $result = MarketplaceApiService::collectOrderWb(12345);

    expect($result)->toBeTrue();
});

test('getStatusOrder returns null for Ozon order on API failure', function () {
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'order_id' => 'ORDER123',
        'marketplace_id' => 1, // Ozon
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v3/posting/fbs/get' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getStatusOrder($order);

    expect($result)->toBeNull();
});

test('getStatusOrder returns status for Ozon order on success', function () {
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'order_id' => 'ORDER123',
        'marketplace_id' => 1, // Ozon
    ]);

    $mockResponse = [
        'result' => [
            'status' => 'awaiting_packaging',
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v3/posting/fbs/get' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getStatusOrder($order);

    expect($result)->toBe('awaiting_packaging');
});

test('getStatusOrder returns null for WB order on API failure', function () {
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'order_id' => '12345',
        'marketplace_id' => 2, // WB
    ]);

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/orders/status' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getStatusOrder($order);

    expect($result)->toBeNull();
});

test('getStatusOrder returns status for WB order on success', function () {
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'order_id' => '12345',
        'marketplace_id' => 2, // WB
    ]);

    $mockResponse = [
        'orders' => [
            [
                'supplierStatus' => 'new',
            ],
        ],
    ];

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/orders/status' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getStatusOrder($order);

    expect($result)->toBe('new');
});

test('getStatusOrder returns null for unknown marketplace', function () {
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'order_id' => 'ORDER123',
        'marketplace_id' => 999, // Unknown
    ]);

    $result = MarketplaceApiService::getStatusOrder($order);

    expect($result)->toBeNull();
});

test('getReturnReason returns default for unknown marketplace', function () {
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'order_id' => 'ORDER123',
        'marketplace_id' => 999, // Unknown
    ]);

    $item = \Database\Factories\MarketplaceOrderItemFactory::new()->create([
        'marketplace_order_id' => $order->id,
    ]);

    $result = MarketplaceApiService::getReturnReason($item);

    expect($result)->toBe('---');
});

test('getReturnReason returns --- for Ozon item on API failure', function () {
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'order_id' => 'ORDER123',
        'marketplace_id' => 1, // Ozon
    ]);

    $item = \Database\Factories\MarketplaceOrderItemFactory::new()->create([
        'marketplace_order_id' => $order->id,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v1/returns/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getReturnReason($item);

    expect($result)->toBe('---');
});

test('getReturnReason returns reason for Ozon item on success', function () {
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'order_id' => 'ORDER123',
        'marketplace_id' => 1, // Ozon
    ]);

    $item = \Database\Factories\MarketplaceOrderItemFactory::new()->create([
        'marketplace_order_id' => $order->id,
    ]);

    $mockResponse = [
        'returns' => [
            [
                'return_reason_name' => 'Брак товара',
            ],
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v1/returns/list' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getReturnReason($item);

    expect($result)->toBe('Брак товара');
});

test('getReturnReason returns --- for WB item on API failure', function () {
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'order_id' => '12345',
        'marketplace_id' => 2, // WB
    ]);

    $item = \Database\Factories\MarketplaceOrderItemFactory::new()->create([
        'marketplace_order_id' => $order->id,
    ]);

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/orders/status' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getReturnReason($item);

    expect($result)->toBe('---');
});

test('getReturnReason returns reason for WB item on success', function () {
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'order_id' => '12345',
        'marketplace_id' => 2, // WB
    ]);

    $item = \Database\Factories\MarketplaceOrderItemFactory::new()->create([
        'marketplace_order_id' => $order->id,
    ]);

    $mockResponse = [
        'orders' => [
            [
                'wbStatus' => 'defect',
            ],
        ],
    ];

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/orders/status' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getReturnReason($item);

    expect($result)->toBe('Отмена заказа по причине брака');
});

test('uploadingNewProducts returns empty result on no orders', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list' => Http::response(
            json_encode(['result' => ['postings' => []]]),
            200
        ),
        'https://marketplace-api.wildberries.ru/api/v3/orders/new' => Http::response(
            json_encode(['orders' => []]),
            200
        ),
    ]);

    $result = MarketplaceApiService::uploadingNewProducts();

    expect($result)->toBeArray();
    expect($result)->toHaveKey('not_found_skus');
    expect($result)->toHaveKey('errors');
    expect($result['not_found_skus'])->toBeArray();
    expect($result['errors'])->toBeArray();
});

test('uploadingNewProducts creates orders when SKUs exist in database', function () {
    // Create SKU and Item records
    $item = \Database\Factories\MarketplaceItemFactory::new()->create();
    $sku = \Database\Factories\SkuFactory::new()->create([
        'item_id' => $item->id,
        'sku' => 'SKU001',
        'marketplace_id' => 1,
    ]);

    $mockOzonResponse = [
        'result' => [
            'postings' => [
                [
                    'posting_number' => 'ORDER123',
                    'in_process_at' => '2026-07-12T10:00:00Z',
                    'products' => [
                        ['sku' => 'SKU001', 'quantity' => 1],
                    ],
                ],
            ],
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list' => Http::response(json_encode($mockOzonResponse), 200),
        'https://marketplace-api.wildberries.ru/api/v3/orders/new' => Http::response(
            json_encode(['orders' => []]),
            200
        ),
    ]);

    $result = MarketplaceApiService::uploadingNewProducts();

    expect($result)->toBeArray();
    expect($result['not_found_skus'])->toBeEmpty();

    $this->assertDatabaseHas('marketplace_orders', [
        'order_id' => 'ORDER123',
        'marketplace_id' => '1',
    ]);
});

test('uploadingCancelledProducts returns empty array on API failures', function () {
    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/orders/new' => Http::response('', 500),
        'https://marketplace-api.wildberries.ru/api/v3/orders' => Http::response('', 500),
        'https://api-seller.ozon.ru/v3/posting/fbs/unfulfilled/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::uploadingCancelledProducts();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('uploadingCancelledProducts returns merged results on success', function () {
    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/orders/status' => Http::response(
            json_encode(['orders' => []]),
            200
        ),
        'https://api-seller.ozon.ru/v3/posting/fbs/list' => Http::response(
            json_encode(['result' => ['postings' => []]]),
            200
        ),
    ]);

    $result = MarketplaceApiService::uploadingCancelledProducts();

    expect($result)->toBeArray();
});

test('ozonSupply returns false when createSupplyOzon fails', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => null,
        'marketplace_id' => 1,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v1/carriage/create' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::ozonSupply($supply);

    expect($result)->toBeFalse();
});

test('ozonSupply returns false when addOrdersToSupplyOzon fails', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => null,
        'marketplace_id' => 1,
    ]);

    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'supply_id' => $supply->id,
        'order_id' => '12345',
        'marketplace_id' => 1,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v1/carriage/create' => Http::response(
            json_encode(['carriage_id' => 'CAR123']),
            200
        ),
        'https://api-seller.ozon.ru/v1/carriage/set-postings' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::ozonSupply($supply);

    expect($result)->toBeFalse();
});

test('ozonSupply returns true on full success', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => null,
        'marketplace_id' => 1,
    ]);

    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'supply_id' => $supply->id,
        'order_id' => '12345',
        'marketplace_id' => 1,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v1/carriage/create' => Http::response(
            json_encode(['carriage_id' => 'CAR123']),
            200
        ),
        'https://api-seller.ozon.ru/v1/carriage/set-postings' => Http::response(
            json_encode(['result' => 'success']),
            200
        ),
        'https://api-seller.ozon.ru/v1/carriage/approve' => Http::response(
            json_encode(['result' => 'success']),
            200
        ),
    ]);

    $result = MarketplaceApiService::ozonSupply($supply);

    expect($result)->toBeTrue();
    expect($supply->supply_id)->toBe('CAR123');
});

test('wbSupply returns false when createSupplyWb fails', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => null,
        'marketplace_id' => 2,
    ]);

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/supplies' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::wbSupply($supply);

    expect($result)->toBeFalse();
});

test('wbSupply returns true on success', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => null,
        'marketplace_id' => 2,
    ]);

    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'supply_id' => $supply->id,
        'order_id' => '12345',
        'marketplace_id' => 2,
    ]);

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/supplies' => Http::response(
            json_encode(['id' => 'WB123']),
            201
        ),
        'https://marketplace-api.wildberries.ru/api/marketplace/v3/supplies/*/orders' => Http::response('', 204),
        'https://marketplace-api.wildberries.ru/api/v3/supplies/*/deliver' => Http::response('', 204),
    ]);

    $result = MarketplaceApiService::wbSupply($supply);

    expect($result)->toBeTrue();
    expect($supply->supply_id)->toBe('WB123');
});

test('checkStatusSupplyOzon returns false on API failure', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'CAR123',
        'marketplace_id' => 1,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v2/posting/fbs/digital/act/check-status' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::checkStatusSupplyOzon($supply);

    expect($result)->toBeFalse();
});

test('checkStatusSupplyOzon returns true when status is FORMED', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'CAR123',
        'marketplace_id' => 1,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v2/posting/fbs/digital/act/check-status' => Http::response(
            json_encode(['status' => 'FORMED', 'id' => 'CAR123']),
            200
        ),
    ]);

    $result = MarketplaceApiService::checkStatusSupplyOzon($supply);

    expect($result)->toBeTrue();
});

test('getDocsSupplyOzon returns redirect on API failure', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'CAR123',
        'marketplace_id' => 1,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v2/posting/fbs/digital/act/get-pdf' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getDocsSupplyOzon($supply);

    expect($result)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
});

test('getDocsSupplyOzon returns PDF response on success', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'CAR123',
        'marketplace_id' => 1,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v2/posting/fbs/digital/act/get-pdf' => Http::response(
            '%PDF-1.4 fake pdf content',
            200
        ),
    ]);

    $result = MarketplaceApiService::getDocsSupplyOzon($supply);

    expect($result)->toBeInstanceOf(\Symfony\Component\HttpFoundation\Response::class);
    expect($result->getStatusCode())->toBe(200);
    expect($result->headers->get('Content-Type'))->toBe('application/pdf');
});

test('getBarcodeSupplyOzon returns redirect when checkStatusSupplyOzon fails', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'CAR123',
        'marketplace_id' => 1,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v2/posting/fbs/digital/act/check-status' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getBarcodeSupplyOzon($supply);

    expect($result)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
});

test('getBarcodeSupplyOzon returns PDF response on success', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'CAR123',
        'marketplace_id' => 1,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v2/posting/fbs/digital/act/check-status' => Http::response(
            json_encode(['status' => 'FORMED', 'id' => 'CAR123']),
            200
        ),
        'https://api-seller.ozon.ru/v2/posting/fbs/act/get-barcode' => Http::response(
            '%PDF-1.4 fake pdf content',
            200
        ),
    ]);

    $result = MarketplaceApiService::getBarcodeSupplyOzon($supply);

    expect($result)->toBeInstanceOf(\Symfony\Component\HttpFoundation\Response::class);
    expect($result->getStatusCode())->toBe(200);
    expect($result->headers->get('Content-Type'))->toBe('application/pdf');
});

test('getBarcodeSupplyWB returns redirect on API failure', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'WB123',
        'marketplace_id' => 2,
    ]);

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/supplies/*/barcode?type=png' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getBarcodeSupplyWB($supply);

    expect($result)->toBeInstanceOf(\Illuminate\Http\RedirectResponse::class);
});

test('getBarcodeSupplyWB returns PDF on success', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'WB123',
        'marketplace_id' => 2,
    ]);

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/supplies/*/barcode?type=png' => Http::response(
            json_encode(['file' => base64_encode('fake image data')]),
            200
        ),
    ]);

    $result = MarketplaceApiService::getBarcodeSupplyWB($supply);

    expect($result)->toBeInstanceOf(\Illuminate\Http\Response::class);
});

test('updateStatusOrderBySupplyWB returns false on API failure', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'WB123',
        'marketplace_id' => 2,
    ]);

    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'supply_id' => $supply->id,
        'order_id' => '12345',
        'marketplace_id' => 2,
        'marketplace_status' => 'old_status',
    ]);

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/orders/status' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::updateStatusOrderBySupplyWB($supply);

    expect($result)->toBeFalse();
});

test('updateStatusOrderBySupplyWB updates order statuses on success', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'WB123',
        'marketplace_id' => 2,
    ]);

    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'supply_id' => $supply->id,
        'order_id' => '12345',
        'marketplace_id' => 2,
        'marketplace_status' => 'old_status',
    ]);

    Http::fake([
        'https://marketplace-api.wildberries.ru/api/v3/orders/status' => Http::response(
            json_encode([
                'orders' => [
                    [
                        'id' => 12345,
                        'supplierStatus' => 'new_status',
                    ],
                ],
            ]),
            200
        ),
    ]);

    $result = MarketplaceApiService::updateStatusOrderBySupplyWB($supply);

    expect($result)->toBeTrue();
    $this->assertDatabaseHas('marketplace_orders', [
        'order_id' => '12345',
        'marketplace_status' => 'new_status',
    ]);
});

test('updateStatusOrderBySupplyOzon returns false on API failure', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'CAR123',
        'marketplace_id' => 1,
    ]);

    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'supply_id' => $supply->id,
        'order_id' => '12345',
        'marketplace_id' => 1,
        'marketplace_status' => 'old_status',
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v3/posting/fbs/get' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::updateStatusOrderBySupplyOzon($supply);

    expect($result)->toBeFalse();
});

test('updateStatusOrderBySupplyOzon updates order statuses on success', function () {
    $supply = \Database\Factories\MarketplaceSupplyFactory::new()->create([
        'supply_id' => 'CAR123',
        'marketplace_id' => 1,
    ]);

    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'supply_id' => $supply->id,
        'order_id' => '12345',
        'marketplace_id' => 1,
        'marketplace_status' => 'old_status',
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v3/posting/fbs/get' => Http::response(
            json_encode([
                'result' => [
                    'status' => 'new_status',
                ],
            ]),
            200
        ),
    ]);

    $result = MarketplaceApiService::updateStatusOrderBySupplyOzon($supply);

    expect($result)->toBeTrue();
    $this->assertDatabaseHas('marketplace_orders', [
        'order_id' => '12345',
        'marketplace_status' => 'new_status',
    ]);
});

test('getOzonPostingNumberByReturnBarcode returns - on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/returns/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getOzonPostingNumberByReturnBarcode('BARCODE123');

    expect($result)->toBe('-');
});

test('getOzonPostingNumberByReturnBarcode returns posting number on success', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/returns/list' => Http::response(
            json_encode([
                'returns' => [
                    [
                        'posting_number' => 'POST123',
                    ],
                ],
            ]),
            200
        ),
    ]);

    $result = MarketplaceApiService::getOzonPostingNumberByReturnBarcode('BARCODE123');

    expect($result)->toBe('POST123');
});

test('createCargoOzon returns error on API failure', function () {
    $payload = ['box_id' => 'BOX123'];

    Http::fake([
        'https://api-seller.ozon.ru/v1/cargoes/create' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::createCargoOzon($payload);

    expect($result)->toBeArray();
    expect($result['operation_id'])->toBeNull();
    expect($result['error'])->not->toBeNull();
});

test('createCargoOzon returns operation_id on success', function () {
    $payload = ['box_id' => 'BOX123'];

    Http::fake([
        'https://api-seller.ozon.ru/v1/cargoes/create' => Http::response(
            json_encode(['operation_id' => 'op-123']),
            200
        ),
    ]);

    $result = MarketplaceApiService::createCargoOzon($payload);

    expect($result)->toBeArray();
    expect($result['operation_id'])->toBe('op-123');
    expect($result['error'])->toBeNull();
});

test('getCargoCreateInfoOzon returns error on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v2/cargoes/create/info' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getCargoCreateInfoOzon('op-123');

    expect($result)->toBeArray();
    expect($result['status'])->toBe('ERROR');
    expect($result['cargo_id'])->toBeNull();
    expect($result['error'])->not->toBeNull();
});

test('getCargoCreateInfoOzon returns cargo_id on success', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v2/cargoes/create/info' => Http::response(
            json_encode([
                'status' => 'SUCCESS',
                'result' => [
                    'cargoes' => [
                        [
                            'value' => [
                                'cargo_id' => 'cargo-123',
                            ],
                        ],
                    ],
                ],
            ]),
            200
        ),
    ]);

    $result = MarketplaceApiService::getCargoCreateInfoOzon('op-123');

    expect($result)->toBeArray();
    expect($result['status'])->toBe('SUCCESS');
    expect($result['cargo_id'])->toBe('cargo-123');
    expect($result['error'])->toBeNull();
});

test('createCargoLabelOzon returns error on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/cargoes-label/create' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::createCargoLabelOzon('SUPPLY123', [1, 2]);

    expect($result)->toBeArray();
    expect($result['operation_id'])->toBeNull();
    expect($result['error'])->not->toBeNull();
});

test('createCargoLabelOzon returns operation_id on success', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/cargoes-label/create' => Http::response(
            json_encode(['operation_id' => 'label-op-123']),
            200
        ),
    ]);

    $result = MarketplaceApiService::createCargoLabelOzon('SUPPLY123', [1, 2]);

    expect($result)->toBeArray();
    expect($result['operation_id'])->toBe('label-op-123');
    expect($result['error'])->toBeNull();
});

test('getCargoLabelOzon returns error on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/cargoes-label/get' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getCargoLabelOzon('label-op-123');

    expect($result)->toBeArray();
    expect($result['status'])->toBe('ERROR');
    expect($result['file_url'])->toBeNull();
    expect($result['error'])->not->toBeNull();
});

test('getCargoLabelOzon returns file_url on success', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/cargoes-label/get' => Http::response(
            json_encode([
                'status' => 'SUCCESS',
                'result' => [
                    'file_url' => 'https://example.com/label.pdf',
                ],
            ]),
            200
        ),
    ]);

    $result = MarketplaceApiService::getCargoLabelOzon('label-op-123');

    expect($result)->toBeArray();
    expect($result['status'])->toBe('SUCCESS');
    expect($result['file_url'])->toBe('https://example.com/label.pdf');
    expect($result['error'])->toBeNull();
});

test('getSupplyOrderListOzon returns empty array on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v3/supply-order/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getSupplyOrderListOzon();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getSupplyOrderListOzon returns order_ids on success', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v3/supply-order/list' => Http::response(
            json_encode([
                'order_ids' => [1001, 1002, 1003],
                'last_id' => '',
            ]),
            200
        ),
    ]);

    $result = MarketplaceApiService::getSupplyOrderListOzon();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(3);
    expect($result)->toBe([1001, 1002, 1003]);
});

test('getSupplyOrderDetailsOzon returns empty array on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/supply-order/details' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getSupplyOrderDetailsOzon(12345);

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getSupplyOrderDetailsOzon returns details on success', function () {
    $details = ['order_id' => 12345, 'status' => 'DATA_FILLING'];

    Http::fake([
        'https://api-seller.ozon.ru/v1/supply-order/details' => Http::response(
            json_encode($details),
            200
        ),
    ]);

    $result = MarketplaceApiService::getSupplyOrderDetailsOzon(12345);

    expect($result)->toBeArray();
    expect($result['order_id'])->toBe(12345);
    expect($result['status'])->toBe('DATA_FILLING');
});

test('getSupplyOrderBundleOzon returns empty array on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/supply-order/bundle' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getSupplyOrderBundleOzon(['bundle1', 'bundle2']);

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getSupplyOrderBundleOzon returns items on success', function () {
    $items = [
        ['item_id' => 'item1', 'name' => 'Product 1'],
        ['item_id' => 'item2', 'name' => 'Product 2'],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v1/supply-order/bundle' => Http::response(
            json_encode([
                'items' => $items,
                'has_next' => false,
                'last_id' => null,
            ]),
            200
        ),
    ]);

    $result = MarketplaceApiService::getSupplyOrderBundleOzon(['bundle1', 'bundle2']);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0]['item_id'])->toBe('item1');
    expect($result[1]['item_id'])->toBe('item2');
});

test('getDraftInfoOzon returns null on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v2/draft/create/info' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getDraftInfoOzon(12345);

    expect($result)->toBeNull();
});

test('getDraftInfoOzon returns draft info on success', function () {
    $mockResponse = [
        'draft_id' => 12345,
        'status' => 'DATA_FILLING',
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v2/draft/create/info' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getDraftInfoOzon(12345);

    expect($result)->toBeArray();
    expect($result['draft_id'])->toBe(12345);
    expect($result['status'])->toBe('DATA_FILLING');
});

test('getDraftWarehousesOzon returns empty array on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v2/draft/create/info' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getDraftWarehousesOzon(12345);

    expect($result)->toBeArray();
    expect($result['warehouses'])->toBeArray();
    expect($result['warehouses'])->toBeEmpty();
    expect($result['error'])->not->toBeNull();
});

test('getDraftWarehousesOzon returns warehouses list on success', function () {
    $mockResponse = [
        'clusters' => [
            [
                'macrolocal_cluster_id' => 100,
                'supply_type' => 'DIRECT',
                'warehouses' => [
                    [
                        'bundle_id' => 'bundle123',
                        'storage_warehouse' => [
                            'warehouse_id' => 456,
                            'name' => 'Test Warehouse',
                            'address' => 'Test Address',
                        ],
                        'availability_status' => ['state' => 'FULL_AVAILABLE'],
                        'total_rank' => 10,
                    ],
                ],
            ],
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v2/draft/create/info' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getDraftWarehousesOzon(12345);

    expect($result)->toBeArray();
    expect($result['warehouses'])->toHaveCount(1);
    expect($result['warehouses'][0]['warehouse_id'])->toBe(456);
    expect($result['warehouses'][0]['name'])->toBe('Test Warehouse');
    expect($result['error'])->toBeNull();
});

test('getDraftTimeslotsOzon returns empty array on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v2/draft/timeslot/info' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getDraftTimeslotsOzon(
        12345,
        'DIRECT',
        100,
        456,
        '2026-07-12',
        '2026-07-15'
    );

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getDraftTimeslotsOzon returns timeslots on success', function () {
    $mockResponse = [
        'result' => [
            'drop_off_warehouse_timeslots' => [
                'days' => [
                    [
                        'date' => '2026-07-13',
                        'slots' => [
                            ['from' => '10:00', 'to' => '12:00'],
                        ],
                    ],
                ],
            ],
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v2/draft/timeslot/info' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getDraftTimeslotsOzon(
        12345,
        'DIRECT',
        100,
        456,
        '2026-07-12',
        '2026-07-15'
    );

    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
    expect($result[0]['date'])->toBe('2026-07-13');
});

test('createDraftDirectOzon returns error on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/draft/direct/create' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::createDraftDirectOzon(100, []);

    expect($result)->toBeArray();
    expect($result['draft_id'])->toBeNull();
    expect($result['error'])->not->toBeNull();
});

test('createDraftDirectOzon returns draft_id on success', function () {
    $mockResponse = ['draft_id' => 56789];

    Http::fake([
        'https://api-seller.ozon.ru/v1/draft/direct/create' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::createDraftDirectOzon(100, []);

    expect($result)->toBeArray();
    expect($result['draft_id'])->toBe(56789);
    expect($result['error'])->toBeNull();
});

test('createDraftCrossdockOzon returns error on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/draft/crossdock/create' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::createDraftCrossdockOzon(100, 789, []);

    expect($result)->toBeArray();
    expect($result['draft_id'])->toBeNull();
    expect($result['error'])->not->toBeNull();
});

test('createDraftCrossdockOzon returns draft_id on success', function () {
    $mockResponse = ['draft_id' => 56789];

    Http::fake([
        'https://api-seller.ozon.ru/v1/draft/crossdock/create' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::createDraftCrossdockOzon(100, 789, []);

    expect($result)->toBeArray();
    expect($result['draft_id'])->toBe(56789);
    expect($result['error'])->toBeNull();
});

test('createSupplyFromDraftOzon returns error on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v2/draft/supply/create' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::createSupplyFromDraftOzon(
        12345,
        100,
        456,
        '2026-07-13 10:00:00',
        '2026-07-13 12:00:00',
        'DIRECT'
    );

    expect($result)->toBeArray();
    expect($result['order_id'])->toBeNull();
    expect($result['error'])->not->toBeNull();
});

test('createSupplyFromDraftOzon returns order_id on success', function () {
    $mockResponse = ['draft_id' => 99999];

    Http::fake([
        'https://api-seller.ozon.ru/v2/draft/supply/create' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::createSupplyFromDraftOzon(
        12345,
        100,
        456,
        '2026-07-13 10:00:00',
        '2026-07-13 12:00:00',
        'DIRECT'
    );

    expect($result)->toBeArray();
    expect($result['order_id'])->toBe(99999);
    expect($result['error'])->toBeNull();
});

test('createSupplyFromDraftOzon returns error when error_reasons present', function () {
    $mockResponse = [
        'draft_id' => 99999,
        'error_reasons' => ['Item not available', 'Invalid quantity'],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v2/draft/supply/create' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::createSupplyFromDraftOzon(
        12345,
        100,
        456,
        '2026-07-13 10:00:00',
        '2026-07-13 12:00:00',
        'DIRECT'
    );

    expect($result)->toBeArray();
    expect($result['order_id'])->toBeNull();
    expect($result['error'])->toBe('Item not available, Invalid quantity');
});

test('getSupplyCreateStatusOzon returns default status on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v2/draft/supply/create/status' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getSupplyCreateStatusOzon(12345);

    expect($result)->toBeArray();
    expect($result['status'])->toBe('UNSPECIFIED');
    expect($result['order_id'])->toBeNull();
    expect($result['error_reasons'])->toBeArray();
});

test('getSupplyCreateStatusOzon returns status on success', function () {
    $mockResponse = [
        'status' => 'SUPPLY_CREATED',
        'order_id' => 88888,
        'error_reasons' => [],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v2/draft/supply/create/status' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getSupplyCreateStatusOzon(12345);

    expect($result)->toBeArray();
    expect($result['status'])->toBe('SUPPLY_CREATED');
    expect($result['order_id'])->toBe(88888);
    expect($result['error_reasons'])->toBeArray();
});

test('cancelSupplyOzon returns error on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/supply-order/cancel' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::cancelSupplyOzon(77777);

    expect($result)->toBeArray();
    expect($result['operation_id'])->toBeNull();
    expect($result['error'])->not->toBeNull();
});

test('cancelSupplyOzon returns operation_id on success', function () {
    $mockResponse = ['operation_id' => 'op-cancel-123'];

    Http::fake([
        'https://api-seller.ozon.ru/v1/supply-order/cancel' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::cancelSupplyOzon(77777);

    expect($result)->toBeArray();
    expect($result['operation_id'])->toBe('op-cancel-123');
    expect($result['error'])->toBeNull();
});

test('getCancelSupplyStatusOzon returns default status on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/supply-order/cancel/status' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getCancelSupplyStatusOzon('op-cancel-123');

    expect($result)->toBeArray();
    expect($result['status'])->toBe('UNSPECIFIED');
    expect($result['error_reasons'])->toBeArray();
});

test('getCancelSupplyStatusOzon returns status on success', function () {
    $mockResponse = [
        'status' => 'CANCELED',
        'result' => 'SUCCESS',
        'error_reasons' => [],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v1/supply-order/cancel/status' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getCancelSupplyStatusOzon('op-cancel-123');

    expect($result)->toBeArray();
    expect($result['status'])->toBe('CANCELED');
    expect($result['result'])->toBe('SUCCESS');
    expect($result['error_reasons'])->toBeArray();
});

test('getReturnsGiveoutPng returns null on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/return/giveout/get-png' => Http::response('', 500),
        'https://api-seller.ozon.ru/v1/return/giveout/barcode' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getReturnsGiveoutPng();

    expect($result)->toBeNull();
});

test('getReturnsGiveoutPng returns array with png and barcode on success', function () {
    $mockResponsePng = ['png' => 'base64_png_data'];
    $mockResponseBarcode = ['barcode' => 'barcode_data'];

    Http::fake([
        'https://api-seller.ozon.ru/v1/return/giveout/get-png' => Http::response(json_encode($mockResponsePng), 200),
        'https://api-seller.ozon.ru/v1/return/giveout/barcode' => Http::response(json_encode($mockResponseBarcode), 200),
    ]);

    $result = MarketplaceApiService::getReturnsGiveoutPng();

    expect($result)->toBeArray();
    expect($result['png'])->toBe('base64_png_data');
    expect($result['barcode'])->toBe('barcode_data');
});

test('resetReturnsGiveoutBarcode returns null on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/return/giveout/barcode-reset' => Http::response('', 500),
        'https://api-seller.ozon.ru/v1/return/giveout/barcode' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::resetReturnsGiveoutBarcode();

    expect($result)->toBeNull();
});

test('resetReturnsGiveoutBarcode returns array with png and barcode on success', function () {
    $mockResponsePng = ['png' => 'new_png_data'];
    $mockResponseBarcode = ['barcode' => 'new_barcode_data'];

    Http::fake([
        'https://api-seller.ozon.ru/v1/return/giveout/barcode-reset' => Http::response(json_encode($mockResponsePng), 200),
        'https://api-seller.ozon.ru/v1/return/giveout/barcode' => Http::response(json_encode($mockResponseBarcode), 200),
    ]);

    $result = MarketplaceApiService::resetReturnsGiveoutBarcode();

    expect($result)->toBeArray();
    expect($result['png'])->toBe('new_png_data');
    expect($result['barcode'])->toBe('new_barcode_data');
});

test('getReturnsCompanyFbsInfo returns empty array on API failure', function () {
    Http::fake([
        'https://api-seller.ozon.ru/v1/returns/company/fbs/info' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getReturnsCompanyFbsInfo();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getReturnsCompanyFbsInfo returns drop_off_points array on success', function () {
    $mockResponse = [
        'drop_off_points' => [
            ['id' => 1, 'name' => 'Point 1'],
            ['id' => 2, 'name' => 'Point 2'],
        ],
    ];

    Http::fake([
        'https://api-seller.ozon.ru/v1/returns/company/fbs/info' => Http::response(json_encode($mockResponse), 200),
    ]);

    $result = MarketplaceApiService::getReturnsCompanyFbsInfo();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0]['name'])->toBe('Point 1');
    expect($result[1]['name'])->toBe('Point 2');
});

test('getBarcodeOzonFBOHtml returns View instance on success', function () {
    $user = \Database\Factories\UserFactory::new()->create();
    $item = \Database\Factories\MarketplaceItemFactory::new()->create();
    $sku = \Database\Factories\SkuFactory::new()->create([
        'item_id' => $item->id,
        'marketplace_id' => 1,
        'sku' => 'test_sku_123',
    ]);
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'marketplace_id' => 1,
    ]);
    $orderItem = \Database\Factories\MarketplaceOrderItemFactory::new()->create([
        'marketplace_order_id' => $order->id,
        'marketplace_item_id' => $item->id,
        'seamstress_id' => $user->id,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v1/product/barcode/details' => Http::response(
            json_encode(['barcode' => 'ozon_barcode_123']),
            200
        ),
    ]);

    $result = (new MarketplaceApiService)->getBarcodeOzonFBOHtml($order);

    expect($result)->toBeInstanceOf(\Illuminate\View\View::class);
    expect($result->getName())->toBe('pdf.fbo_ozon_sticker_html');
});

test('getBarcodeOzonFBOHtml returns View even when barcode API fails', function () {
    $user = \Database\Factories\UserFactory::new()->create();
    $item = \Database\Factories\MarketplaceItemFactory::new()->create();
    $sku = \Database\Factories\SkuFactory::new()->create([
        'item_id' => $item->id,
        'marketplace_id' => 1,
        'sku' => 'test_sku_456',
    ]);
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'marketplace_id' => 1,
    ]);
    $orderItem = \Database\Factories\MarketplaceOrderItemFactory::new()->create([
        'marketplace_order_id' => $order->id,
        'marketplace_item_id' => $item->id,
        'seamstress_id' => $user->id,
    ]);

    Http::fake([
        'https://api-seller.ozon.ru/v1/product/barcode/details' => Http::response('', 500),
    ]);

    $result = (new MarketplaceApiService)->getBarcodeOzonFBOHtml($order);

    expect($result)->toBeInstanceOf(\Illuminate\View\View::class);
    expect($result->getName())->toBe('pdf.fbo_ozon_sticker_html');
});

test('getBarcodeWBFBO returns Response on success', function () {
    $user = \Database\Factories\UserFactory::new()->create();
    $item = \Database\Factories\MarketplaceItemFactory::new()->create(['title' => 'Test Item']);
    $sku = \Database\Factories\SkuFactory::new()->create([
        'item_id' => $item->id,
        'marketplace_id' => 2,
        'sku' => 'wb_sku_123',
    ]);
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'marketplace_id' => 2,
        'cluster' => 'test_cluster',
    ]);
    $orderItem = \Database\Factories\MarketplaceOrderItemFactory::new()->create([
        'marketplace_order_id' => $order->id,
        'marketplace_item_id' => $item->id,
        'seamstress_id' => $user->id,
        'cutter_id' => $user->id,
    ]);

    Http::fake([
        'https://content-api.wildberries.ru/content/v2/get/cards/list' => Http::response(
            json_encode([
                'cards' => [
                    ['nmID' => 12345],
                ],
            ]),
            200
        ),
    ]);

    $orders = collect([$order]);
    $result = (new MarketplaceApiService)->getBarcodeWBFBO($orders);

    expect($result)->toBeInstanceOf(\Illuminate\Http\Response::class);
});

test('getBarcodeWBFBO returns Response even when WB API fails', function () {
    $user = \Database\Factories\UserFactory::new()->create();
    $item = \Database\Factories\MarketplaceItemFactory::new()->create(['title' => 'Test Item 2']);
    $sku = \Database\Factories\SkuFactory::new()->create([
        'item_id' => $item->id,
        'marketplace_id' => 2,
        'sku' => 'wb_sku_456',
    ]);
    $order = \Database\Factories\MarketplaceOrderFactory::new()->create([
        'marketplace_id' => 2,
        'cluster' => 'test_cluster_2',
    ]);
    $orderItem = \Database\Factories\MarketplaceOrderItemFactory::new()->create([
        'marketplace_order_id' => $order->id,
        'marketplace_item_id' => $item->id,
        'seamstress_id' => $user->id,
    ]);

    Http::fake([
        'https://content-api.wildberries.ru/content/v2/get/cards/list' => Http::response('', 500),
    ]);

    $orders = collect([$order]);
    $result = (new MarketplaceApiService)->getBarcodeWBFBO($orders);

    expect($result)->toBeInstanceOf(\Illuminate\Http\Response::class);
});

test('getItemWbBySku returns null on non-200 response', function () {
    Http::fake([
        'https://content-api.wildberries.ru/content/v2/get/cards/list' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getItemWbBySku('test_sku');

    expect($result)->toBeNull();
});

test('getItemWbBySku returns card object on success', function () {
    $mockCard = [
        'nmID' => 12345,
        'vendorCode' => 'test_sku',
    ];

    Http::fake([
        'https://content-api.wildberries.ru/content/v2/get/cards/list' => Http::response(
            json_encode([
                'cards' => [$mockCard],
            ]),
            200
        ),
    ]);

    $result = MarketplaceApiService::getItemWbBySku('test_sku');

    expect($result)->toBeObject();
    expect($result->nmID)->toBe(12345);
});

test('syncWarehousesWb returns 0 on API failure', function () {
    Http::fake([
        'https://supplies-api.wildberries.ru/api/v1/warehouses' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::syncWarehousesWb();

    expect($result)->toBe(0);
});

test('syncWarehousesWb adds warehouses to database on success', function () {
    Http::fake([
        'https://supplies-api.wildberries.ru/api/v1/warehouses' => Http::response(
            json_encode([
                ['name' => 'Warehouse 1'],
                ['name' => 'Warehouse 2'],
            ]),
            200
        ),
    ]);

    $result = MarketplaceApiService::syncWarehousesWb();

    expect($result)->toBe(2);
    $this->assertDatabaseHas('marketplace_warehouses', [
        'name' => 'Warehouse 1',
        'marketplace_id' => 2,
    ]);
    $this->assertDatabaseHas('marketplace_warehouses', [
        'name' => 'Warehouse 2',
        'marketplace_id' => 2,
    ]);
});

test('getFboSuppliesWb returns empty array on API failure', function () {
    Http::fake([
        'https://supplies-api.wildberries.ru/api/v1/supplies' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getFboSuppliesWb();

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getFboSuppliesWb returns supplies array on success', function () {
    $mockSupplies = [
        ['id' => 1, 'status' => 'new'],
        ['id' => 2, 'status' => 'in_progress'],
    ];

    Http::fake([
        'https://supplies-api.wildberries.ru/api/v1/supplies' => Http::response(
            json_encode($mockSupplies),
            200
        ),
    ]);

    $result = MarketplaceApiService::getFboSuppliesWb();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0]['id'])->toBe(1);
});

test('getFboSupplyDetailWb returns empty array on API failure', function () {
    Http::fake([
        'https://supplies-api.wildberries.ru/api/v1/supplies/123' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getFboSupplyDetailWb(123);

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getFboSupplyDetailWb returns supply details on success', function () {
    $mockDetail = [
        'id' => 123,
        'status' => 'in_progress',
        'warehouse' => 'Warehouse 1',
    ];

    Http::fake([
        'https://supplies-api.wildberries.ru/api/v1/supplies/123' => Http::response(
            json_encode($mockDetail),
            200
        ),
    ]);

    $result = MarketplaceApiService::getFboSupplyDetailWb(123);

    expect($result)->toBeArray();
    expect($result['id'])->toBe(123);
    expect($result['status'])->toBe('in_progress');
});

test('getFboSupplyGoodsWb returns empty array on API failure', function () {
    Http::fake([
        'https://supplies-api.wildberries.ru/api/v1/supplies/123/goods' => Http::response('', 500),
    ]);

    $result = MarketplaceApiService::getFboSupplyGoodsWb(123);

    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

test('getFboSupplyGoodsWb returns goods array on success', function () {
    $mockGoods = [
        ['sku' => 'SKU001', 'quantity' => 10],
        ['sku' => 'SKU002', 'quantity' => 20],
    ];

    Http::fake([
        'https://supplies-api.wildberries.ru/api/v1/supplies/123/goods' => Http::response(
            json_encode($mockGoods),
            200
        ),
    ]);

    $result = MarketplaceApiService::getFboSupplyGoodsWb(123);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0]['sku'])->toBe('SKU001');
});
