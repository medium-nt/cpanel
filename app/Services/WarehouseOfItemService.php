<?php

namespace App\Services;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Sku;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarehouseOfItemService
{
    public function getFiltered(Request $request): Builder
    {
        $items = MarketplaceOrderItem::query()
            ->join('marketplace_items', 'marketplace_order_items.marketplace_item_id', '=', 'marketplace_items.id');

        $items = $this->applyFilters($items, $request);

        return $items
            ->with(['item', 'shelf', 'marketplaceOrder'])
            ->select('marketplace_order_items.*', 'marketplace_items.title', 'marketplace_items.width', 'marketplace_items.height');
    }

    /**
     * Применяет фильтры к запросу товаров склада.
     */
    private function applyFilters(Builder $items, Request $request): Builder
    {
        if ($request->has('status')) {
            $items = $items->where('marketplace_order_items.status', $request->status);
        } else {
            $items = $items->whereIn('marketplace_order_items.status', [9, 10, 11, 12, 13]);
        }

        if ($request->has('material')) {
            $items = $items->where('marketplace_items.title', 'like', '%'.$request->material.'%');
        }

        if ($request->has('width')) {
            $items = $items->where('marketplace_items.width', $request->width);
        }

        if ($request->has('height')) {
            $items = $items->where('marketplace_items.height', $request->height);
        }

        if ($request->has('shelf')) {
            $items = $items->where('marketplace_order_items.shelf_id', $request->shelf);
        }

        return $items;
    }

    /**
     * Экспортирует отфильтрованные товары склада в Excel (.xlsx) с группировкой по материалу, ширине и длине.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $items = MarketplaceOrderItem::query()
            ->join('marketplace_items', 'marketplace_order_items.marketplace_item_id', '=', 'marketplace_items.id');

        $items = $this->applyFilters($items, $request);

        $grouped = $items
            ->selectRaw(
                'marketplace_items.article, '
                .'marketplace_items.title, '
                .'marketplace_items.width, '
                .'marketplace_items.height, '
                .'SUM(marketplace_order_items.quantity) as total_quantity'
            )
            ->groupBy([
                'marketplace_items.article',
                'marketplace_items.title',
                'marketplace_items.width',
                'marketplace_items.height',
            ])
            ->orderBy('marketplace_items.title')
            ->orderBy('marketplace_items.width')
            ->orderBy('marketplace_items.height')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'артикул');
        $sheet->setCellValue('B1', 'имя (необязательно)');
        $sheet->setCellValue('C1', 'количество');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $row = 2;
        foreach ($grouped as $item) {
            $sheet->setCellValue('A'.$row, $item->article);
            $sheet->setCellValue('B'.$row, $item->title.' '.$item->width.'x'.$item->height);
            $sheet->setCellValue('C'.$row, $item->total_quantity);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            callback: function () use ($writer) {
                $writer->save('php://output');
            },
            name: 'warehouse_storage_'.now()->format('Y-m-d_H-i-s').'.xlsx',
            headers: [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }

    /**
     * Экспортирует отфильтрованные товары склада в Excel (.xlsx) в формате WB (Wildberries).
     */
    public function exportExcelWb(Request $request): StreamedResponse
    {
        $items = MarketplaceOrderItem::query()
            ->join('marketplace_items', 'marketplace_order_items.marketplace_item_id', '=', 'marketplace_items.id')
            ->join('skus', function ($join) {
                $join->on('skus.item_id', '=', 'marketplace_items.id')
                    ->where('skus.marketplace_id', 2);
            });

        $items = $this->applyFilters($items, $request);

        $grouped = $items
            ->selectRaw(
                'skus.sku as barcode, '
                .'marketplace_items.article, '
                .'SUM(marketplace_order_items.quantity) as total_quantity'
            )
            ->groupBy([
                'skus.sku',
                'marketplace_items.article',
            ])
            ->orderBy('marketplace_items.article')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Баркод');
        $sheet->setCellValue('B1', 'Количество, шт.');
        $sheet->setCellValue('C1', 'Артикул поставщика');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $row = 2;
        foreach ($grouped as $item) {
            $sheet->setCellValue('A'.$row, $item->barcode);
            $sheet->setCellValue('B'.$row, $item->total_quantity);
            $sheet->setCellValue('C'.$row, $item->article);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            callback: function () use ($writer) {
                $writer->save('php://output');
            },
            name: 'warehouse_storage_wb_'.now()->format('Y-m-d_H-i-s').'.xlsx',
            headers: [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }

    public function getStorageBarcode(MarketplaceOrderItem $marketplace_item): string
    {
        $barcode = $marketplace_item->storage_barcode;

        if (! $barcode) {
            $barcode = $this->generateBarcode($marketplace_item->id);

            $marketplace_item->storage_barcode = $barcode;
            $marketplace_item->save();
        }

        return $barcode;
    }

    private function generateBarcode(int $id): string
    {
        //  используется алгоритм Луна.
        $base = str_pad($id, 8, '0', STR_PAD_LEFT);
        $sum = 0;

        foreach (str_split(strrev($base)) as $i => $digit) {
            $n = (int) $digit * ($i % 2 === 0 ? 2 : 1);
            $sum += $n > 9 ? $n - 9 : $n;
        }

        return $base.((10 - $sum % 10) % 10);
    }

    public function saveItemToStorage(MarketplaceOrderItem $item, int $shelfId): void
    {
        $item->shelf_id = $shelfId;
        $item->status = 11;
        $item->save();

        MarketplaceOrder::query()
            ->where('id', $item->marketplace_order_id)
            ->update([
                'returned_at' => now(),
                'status' => 9,
            ]);

        Log::channel('items')
            ->info('Товар с заказа '.$item->marketplace_order_id.' помещен на склад хранения на полку '.$shelfId);
    }

    public function findRefundItemByBarcode($barcode): array
    {
        if (! $barcode) {
            return [
                'message' => 'Введите штрихкод',
                'marketplace_item' => null,
                'marketplace_items' => collect(),
                'returnReason' => '',
            ];
        }

        // если это стикер OZON FBS
        if (! is_array($barcode) && mb_strlen(trim($barcode)) == 15) {
            $barcode = MarketplaceApiService::getOzonPostingNumberByBarcode($barcode);
        }

        // если это стикер OZON возврат
        if (! is_array($barcode) && str_starts_with(trim($barcode), 'ii')) {
            $barcode = MarketplaceApiService::getOzonPostingNumberByReturnBarcode($barcode);
        }

        $isFBO = false;

        // если это стикер OZON FBO
        if (! is_array($barcode) && str_starts_with(trim($barcode), 'OZN')) {
            $sku = trim($barcode, 'OZN');

            $barcode = Sku::query()->where('sku', $sku)
                ->first()?->item->id ?? '-';

            $isFBO = true;
        }

        $items = MarketplaceOrderItem::query()
            ->join('marketplace_orders', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
            ->join('marketplace_items', 'marketplace_items.id', '=', 'marketplace_order_items.marketplace_item_id')
            ->with('item')
            //  TO_DO: вернуть фильтр по статусу
//                  ->whereIn('marketplace_order_items.status', [10])
            ->whereIn('marketplace_order_items.status', [3, 11])
            ->where(function ($query) use ($barcode) {
                $query->where('marketplace_orders.order_id', $barcode)
                    ->orWhere('marketplace_order_items.storage_barcode', $barcode)
                    ->orWhere('part_b', $barcode)
                    ->orWhere('barcode', $barcode)
                    ->orWhere('marketplace_items.id', $barcode);
            })->when($isFBO, function ($query) {
                $query->where('marketplace_orders.fulfillment_type', 'FBO');
            })->select('marketplace_order_items.*')
            ->get();

        if ($items->isEmpty()) {
            return [
                'message' => 'Нет такого заказа',
                'marketplace_item' => null,
                'marketplace_items' => collect(),
                'returnReason' => '',
            ];
        }

        if ($items->count() > 1) {
            return [
                'message' => 'Найдено несколько заказов. Выберите нужный:',
                'marketplace_item' => null,
                'marketplace_items' => $items,
                'returnReason' => '',
            ];
        }

        $item = $items->first();

        if ($item->status == 11) {
            return [
                'message' => 'Товар уже находится на складе',
                'marketplace_item' => null,
                'marketplace_items' => collect(),
                'returnReason' => '',
            ];
        }

        $returnReason = MarketplaceApiService::getReturnReason($item);

        return [
            'message' => '',
            'marketplace_item' => $item,
            'marketplace_items' => collect(),
            'returnReason' => $returnReason,
        ];
    }

    public function getInspectionStats(): array
    {
        return [
            'on_inspection' => MarketplaceOrderItem::query()
                ->where('status', 12) // На проверке
                ->count(),
            'returns' => MarketplaceOrderItem::query()
                ->where('status', 10) // Готовые к осмотру
                ->count(),
            'inspected' => MarketplaceOrderItem::query()
                ->where('status', 15) // Осмотрено
                ->count(),
            'defect' => MarketplaceOrderItem::query()
                ->where('status', 16) // На утилизацию
                ->count(),
            'from_workshop' => MarketplaceOrderItem::query()
                ->where('status', 18) // Забран с цеха
                ->count(),
            'to_utilize' => MarketplaceOrderItem::query()
                ->where('status', 19) // Утилизировать
                ->count(),
        ];
    }

    public function getCreateItems($validatedData, MarketplaceItem $item): array
    {
        $marketplaceItems = [];

        for ($i = 0; $i < $validatedData['quantity']; $i++) {

            $marketplaceOrder = MarketplaceOrder::query()->create([
                'order_id' => '...',
                'marketplace_id' => 1,
                'fulfillment_type' => 'FBO',
                'status' => 9,
                'completed_at' => now(),
                'returned_at' => now(),
                'created_at' => now(),
            ]);

            $marketplaceOrderItem = MarketplaceOrderItem::query()->create([
                'marketplace_order_id' => $marketplaceOrder->id,
                'marketplace_item_id' => $item->id,
                'shelf_id' => $validatedData['shelf_id'],
                'quantity' => 1,
                'price' => 0,
                'status' => 11,
                'seamstress_id' => $validatedData['seamstress_id'],
                'cutter_id' => $validatedData['cutter_id'],
                'completed_at' => now()->startOfDay()->subDays(2),
                'created_at' => Carbon::parse($marketplaceOrder->created_at),
            ]);

            $marketplaceOrderItem->storage_barcode = $this->getStorageBarcode($marketplaceOrderItem);
            $marketplaceOrderItem->save();

            $marketplaceOrder->order_id = 'под товар '.$marketplaceOrderItem->id;
            $marketplaceOrder->save();

            $marketplaceItems[] = $marketplaceOrderItem->id;
        }

        return $marketplaceItems;
    }
}
