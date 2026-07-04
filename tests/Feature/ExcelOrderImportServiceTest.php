<?php

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\Role;
use App\Models\User;
use App\Services\ExcelOrderImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

// Очищаем маркетплейс-данные — тесты проверяют точные счётчики и форматы order_id.
beforeEach(function () {
    $this->cleanTables = ['marketplace_orders', 'marketplace_order_items'];
    $this->cleanTables();
});

/**
 * Invoke the private static getNextDailySequence() via reflection.
 */
function excelImportNextDailySequence(string $dateStr): int
{
    $method = new ReflectionMethod(ExcelOrderImportService::class, 'getNextDailySequence');
    $method->setAccessible(true);

    return $method->invoke(null, $dateStr);
}

test('parseFile returns headers and empty rows when only header row exists', function () {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Артикул');
    $sheet->setCellValue('B1', 'Количество');

    $filePath = sys_get_temp_dir().'/headers_only.xlsx';
    IOFactory::createWriter($spreadsheet, 'Xlsx')->save($filePath);

    $result = ExcelOrderImportService::parseFile($filePath);

    expect($result['headers'])->toEqual(['Артикул', 'Количество']);
    expect($result['rows'])->toBeEmpty();

    unlink($filePath);
});

test('parseFile correctly parses Excel file with headers and data', function () {
    Storage::fake('local');

    // Create a simple Excel file in memory
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Add headers
    $sheet->setCellValue('A1', 'Артикул');
    $sheet->setCellValue('B1', 'Количество');

    // Add data
    $sheet->setCellValue('A2', 'SKU123');
    $sheet->setCellValue('B2', '5');
    $sheet->setCellValue('A3', 'SKU456');
    $sheet->setCellValue('B3', '3');

    // Save to temporary file
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $filePath = sys_get_temp_dir().'/test.xlsx';
    $writer->save($filePath);

    $result = ExcelOrderImportService::parseFile($filePath);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('headers');
    expect($result)->toHaveKey('rows');
    expect($result['headers'])->toEqual(['Артикул', 'Количество']);
    expect($result['rows'])->toHaveCount(2);
    expect($result['rows'][0])->toEqual(['SKU123', '5']);
    expect($result['rows'][1])->toEqual(['SKU456', '3']);

    // Clean up
    unlink($filePath);
});

test('parseFile skips empty rows', function () {
    Storage::fake('local');

    // Create a simple Excel file
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Add headers
    $sheet->setCellValue('A1', 'Артикул');
    $sheet->setCellValue('B1', 'Количество');

    // Add data with empty row
    $sheet->setCellValue('A2', 'SKU123');
    $sheet->setCellValue('B2', '5');
    $sheet->setCellValue('A3', ''); // Empty
    $sheet->setCellValue('B3', '');
    $sheet->setCellValue('A4', 'SKU456');
    $sheet->setCellValue('B4', '3');

    $filePath = sys_get_temp_dir().'/test.xlsx';
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($filePath);

    $result = ExcelOrderImportService::parseFile($filePath);

    expect($result['rows'])->toHaveCount(2);
    expect($result['rows'][0])->toEqual(['SKU123', '5']);
    expect($result['rows'][1])->toEqual(['SKU456', '3']);

    unlink($filePath);
});

test('matchRow returns error for empty article value', function () {
    $result = ExcelOrderImportService::matchRow('');

    expect($result)->toBeArray();
    expect($result)->toHaveKey('item_id');
    expect($result)->toHaveKey('error');
    expect($result)->toHaveKey('item_title');
    expect($result['item_id'])->toBeNull();
    expect($result['error'])->toBe('Пустое значение');
    expect($result['item_title'])->toBeNull();
});

test('matchRow returns error when item not found', function () {
    $result = ExcelOrderImportService::matchRow('NONEXISTENT_ARTICLE');

    expect($result)->toBeArray();
    expect($result['item_id'])->toBeNull();
    expect($result['error'])->toBe('Товар не найден по артикулу: NONEXISTENT_ARTICLE');
    expect($result['item_title'])->toBeNull();
});

test('matchRow returns item data when found', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $item = MarketplaceItem::factory()->create([
        'article' => 'TEST_ARTICLE',
        'title' => 'Test Product',
        'width' => 100,
        'height' => 200,
    ]);

    $result = ExcelOrderImportService::matchRow('TEST_ARTICLE');

    expect($result)->toBeArray();
    expect($result['item_id'])->toBe($item->id);
    expect($result['error'])->toBeNull();
    expect($result['item_title'])->toBe('TEST_ARTICLE — Test Product 100x200');
});

test('createOrders creates correct number of orders', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $item = MarketplaceItem::factory()->create();

    $rows = [
        ['item_id' => $item->id, 'quantity' => 2, 'sku_raw' => 'SKU123'],
        ['item_id' => $item->id, 'quantity' => 1, 'sku_raw' => 'SKU456'],
    ];

    $result = ExcelOrderImportService::createOrders($rows, 1, 'Test Cluster');

    expect($result)->toBe(3); // 2 + 1 = 3 orders
    $this->assertDatabaseCount('marketplace_orders', 3);
    $this->assertDatabaseCount('marketplace_order_items', 3);
});

test('createOrders uses correct order ID format', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $item = MarketplaceItem::factory()->create();

    $rows = [
        ['item_id' => $item->id, 'quantity' => 1, 'sku_raw' => 'SKU123'],
    ];

    ExcelOrderImportService::createOrders($rows, 1, 'Test Cluster');

    $order = MarketplaceOrder::first();
    expect($order->order_id)->toMatch('/^OZ-\d{4}-\d+-\d+$/'); // OZ-DDMM-SEQ-SEQ
    expect($order->marketplace_id)->toBe(1);
    expect($order->fulfillment_type)->toBe('FBO');
    expect($order->status)->toBe('0'); // status хранится как varchar
    expect($order->cluster)->toBe('Test Cluster');
});

test('createOrders uses correct order ID format for WB', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $item = MarketplaceItem::factory()->create();

    $rows = [
        ['item_id' => $item->id, 'quantity' => 1, 'sku_raw' => 'SKU123'],
    ];

    ExcelOrderImportService::createOrders($rows, 2, 'Test Cluster');

    $order = MarketplaceOrder::first();
    expect($order->order_id)->toMatch('/^WB-\d{4}-\d+-\d+$/'); // WB-DDMM-SEQ-SEQ
});

test('createOrders creates separate orders for multiple quantities', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $item = MarketplaceItem::factory()->create();

    $rows = [
        ['item_id' => $item->id, 'quantity' => 3, 'sku_raw' => 'SKU123'],
    ];

    ExcelOrderImportService::createOrders($rows, 1, 'Test Cluster');

    // 1 строка с quantity=3 → 3 отдельных заказа, каждый с 1 item'ом
    $orders = MarketplaceOrder::all();
    expect($orders)->toHaveCount(3);

    // Каждый order item имеет quantity = 1
    $orders->each(function ($order) {
        $orderItem = $order->items->first();
        expect($orderItem->quantity)->toBe(1);
    });
});

test('createOrders handles minimum quantity correctly', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $item = MarketplaceItem::factory()->create();

    $rows = [
        ['item_id' => $item->id, 'quantity' => 0, 'sku_raw' => 'SKU123'], // Should become 1
        ['item_id' => $item->id, 'quantity' => -1, 'sku_raw' => 'SKU456'], // Should become 1
    ];

    $result = ExcelOrderImportService::createOrders($rows, 1, 'Test Cluster');

    expect($result)->toBe(2); // Both quantities become 1
});

test('getNextDailySequence returns 1 when no previous orders exist', function () {
    // Очищаем все заказы
    MarketplaceOrder::query()->delete();

    $dateStr = now()->format('dm');
    $result = excelImportNextDailySequence($dateStr);

    expect($result)->toBe(1);
});

test('getNextDailySequence returns last order fourth segment plus one', function () {
    $dateStr = now()->format('dm');

    // Фактическое поведение: метод берёт 4-й сегмент order_id (IMPORT_SEQ)
    // последнего заказа и прибавляет 1, несмотря на имя "DailySequence".
    // order_id формат: {MP}-{DDMM}-{DAILY_SEQ}-{IMPORT_SEQ}
    MarketplaceOrder::create([
        'order_id' => "OZ-{$dateStr}-0001-5",
        'marketplace_id' => 1,
        'status' => '0',
    ]);

    $result = excelImportNextDailySequence($dateStr);

    expect($result)->toBe(6); // IMPORT_SEQ=5 → +1
});

test('getNextDailySequence returns 1 when last order import segment is not numeric', function () {
    $dateStr = now()->format('dm');

    // 4-й сегмент не числовой → метод не может вычислить sequence, возвращает 1
    MarketplaceOrder::create([
        'order_id' => "OZ-{$dateStr}-0001-NOTNUM",
        'marketplace_id' => 1,
        'status' => '0',
    ]);

    $result = excelImportNextDailySequence($dateStr);

    expect($result)->toBe(1);
});
