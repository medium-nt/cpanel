<?php

namespace App\Services;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Sku;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ExcelOrderImportService
{
    /**
     * Parse uploaded Excel/CSV file into headers and rows.
     *
     * @return array{headers: list<string>, rows: list<array<int, string|null>>}
     */
    public static function parseFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_values($rows[0]);
        unset($rows[0]);

        $dataRows = [];
        foreach ($rows as $row) {
            $values = array_values($row);
            if (collect($values)->filter(fn ($v) => $v !== null && $v !== '')->isEmpty()) {
                continue;
            }
            $dataRows[] = $values;
        }

        return ['headers' => $headers, 'rows' => $dataRows];
    }

    /**
     * Match a SKU/barcode value to a MarketplaceItem via the Sku model.
     * Strips "OZN" prefix if present and determines marketplace_id.
     *
     * @return array{item_id: int|null, marketplace_id: int, error: string|null, item_title: string|null}
     */
    public static function matchRow(string $skuValue): array
    {
        $skuValue = trim((string) $skuValue);

        if ($skuValue === '') {
            return [
                'item_id' => null,
                'marketplace_id' => 1,
                'error' => 'Пустое значение',
                'item_title' => null,
            ];
        }

        $marketplaceId = 1;
        $cleanValue = $skuValue;

        if (mb_strtoupper(mb_substr($skuValue, 0, 3)) === 'OZN') {
            $cleanValue = mb_substr($skuValue, 3);
            $marketplaceId = 1;
        } else {
            $marketplaceId = 2;
        }

        $cleanValue = trim($cleanValue);

        $sku = Sku::query()->where('sku', $cleanValue)->first();

        if ($sku === null) {
            return [
                'item_id' => null,
                'marketplace_id' => $marketplaceId,
                'error' => 'SKU не найден: '.$cleanValue,
                'item_title' => null,
            ];
        }

        $item = MarketplaceItem::query()->find($sku->item_id);
        $itemTitle = $item ? "{$item->title} {$item->width}x{$item->height}" : null;

        return [
            'item_id' => $sku->item_id,
            'marketplace_id' => $marketplaceId,
            'error' => null,
            'item_title' => $itemTitle,
        ];
    }

    /**
     * Create FBO orders from validated rows.
     * Each row creates N separate orders (N = quantity).
     * Order ID format: FBO-{MARKETPLACE}-{DDMM}-{DAILY_SEQ}-{IMPORT_SEQ}
     * Example: FBO-OZON-0204-0001-1
     *
     * @param  array<int, array{item_id: int, quantity: int, cluster: string|null, marketplace_id: int, sku_raw: string}>  $rows
     * @return int Number of orders created
     *
     * @throws Throwable
     */
    public static function createOrders(array $rows): int
    {
        $dateStr = now()->format('dm');
        $dailySeq = self::getNextDailySequence($dateStr);
        $importSeq = 1;
        $createdCount = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $itemId = $row['item_id'];
                $quantity = max((int) $row['quantity'], 1);
                $cluster = $row['cluster'] ?? null;
                $marketplaceId = $row['marketplace_id'];
                $marketplaceName = $marketplaceId == 1 ? 'OZON' : 'WB';

                for ($i = 1; $i <= $quantity; $i++) {
                    $orderId = sprintf('FBO-%s-%s-%d-%d', $marketplaceName, $dateStr, $dailySeq, $importSeq++);

                    $marketplaceOrder = MarketplaceOrder::query()->create([
                        'order_id' => $orderId,
                        'marketplace_id' => $marketplaceId,
                        'fulfillment_type' => 'FBO',
                        'status' => 0,
                        'cluster' => $cluster ?: null,
                    ]);

                    MarketplaceOrderItem::query()->create([
                        'marketplace_order_id' => $marketplaceOrder->id,
                        'marketplace_item_id' => $itemId,
                        'quantity' => 1,
                        'price' => 0,
                    ]);

                    $createdCount++;
                }
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::channel('orders')->error('Excel import error: '.$e->getMessage());
            throw $e;
        }

        Log::channel('orders')->notice("Excel импорт: создано {$createdCount} заказов (партия {$dailySeq} за {$dateStr})");

        return $createdCount;
    }

    /**
     * Determine the next daily sequence number for orders.
     * Looks at existing orders matching FBO-{MARKETPLACE}-{DDMM}-{SEQ}-%.
     */
    private static function getNextDailySequence(string $dateStr): int
    {
        $lastOrder = MarketplaceOrder::query()
            ->where('order_id', 'LIKE', 'FBO-%-'.$dateStr.'-%')
            ->orderByDesc('id')
            ->first();

        if ($lastOrder) {
            $parts = explode('-', $lastOrder->order_id);
            if (isset($parts[3]) && is_numeric($parts[3])) {
                return (int) $parts[3] + 1;
            }
        }

        return 1;
    }
}
