<?php

namespace App\Services;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
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
     * Match an article value to a MarketplaceItem.
     *
     * @return array{item_id: int|null, error: string|null, item_title: string|null}
     */
    public static function matchRow(string $articleValue): array
    {
        $articleValue = trim((string) $articleValue);

        if ($articleValue === '') {
            return [
                'item_id' => null,
                'error' => 'Пустое значение',
                'item_title' => null,
            ];
        }

        $item = MarketplaceItem::query()->where('article', $articleValue)->first();

        if ($item === null) {
            return [
                'item_id' => null,
                'error' => 'Товар не найден по артикулу: '.$articleValue,
                'item_title' => null,
            ];
        }

        $itemTitle = "{$item->article} — {$item->title} {$item->width}x{$item->height}";

        return [
            'item_id' => $item->id,
            'error' => null,
            'item_title' => $itemTitle,
        ];
    }

    /**
     * Create FBO orders from validated rows.
     * Each row creates N separate orders (N = quantity).
     * Order ID format: {MARKETPLACE}-{DDMM}-{DAILY_SEQ}-{IMPORT_SEQ}
     * Example: OZ-0204-0001-1
     *
     * @param  array<int, array{item_id: int, quantity: int, sku_raw: string}>  $rows
     * @return int Number of orders created
     *
     * @throws Throwable
     */
    public static function createOrders(array $rows, int $marketplaceId, string $cluster): int
    {
        $dateStr = now()->format('dm');
        $dailySeq = self::getNextDailySequence($dateStr);
        $importSeq = 1;
        $createdCount = 0;
        $marketplaceName = $marketplaceId == 1 ? 'OZ' : 'WB';

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $itemId = $row['item_id'];
                $quantity = max((int) $row['quantity'], 1);

                for ($i = 1; $i <= $quantity; $i++) {
                    $orderId = sprintf('%s-%s-%d-%d', $marketplaceName, $dateStr, $dailySeq, $importSeq++);

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
     * Looks at existing orders matching {MARKETPLACE}-{DDMM}-{SEQ}-%.
     */
    private static function getNextDailySequence(string $dateStr): int
    {
        $lastOrder = MarketplaceOrder::query()
            ->where('order_id', 'LIKE', '%-'.$dateStr.'-%')
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
