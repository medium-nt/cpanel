<?php

namespace App\Livewire;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceWarehouse;
use App\Models\ProductSticker;
use App\Models\Sku;
use App\Services\ExcelOrderImportService;
use App\Services\MarketplaceApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class StickerTapeImport extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public $excelFile = null;

    public array $fileHeaders = [];

    public array $columnMap = [
        'article' => '',
        'quantity' => '',
    ];

    public array $rows = [];

    public array $processedRows = [];

    public string $errorMessage = '';

    public string $globalCluster = '';

    public int $globalMarketplace = 0;

    /** @var array<int, array<int, string>> Склады, сгруппированные по marketplace_id */
    public array $warehouses = [];

    /** Инициализирует компонент и загружает список складов, сгруппированных по маркетплейсам. */
    public function mount(): void
    {
        $this->warehouses = MarketplaceWarehouse::query()
            ->orderBy('name')
            ->get()
            ->groupBy('marketplace_id')
            ->map(fn ($group) => $group->pluck('name', 'name')->toArray())
            ->toArray();
    }

    /** Загружает и парсит Excel-файл, определяет заголовки и строки данных. */
    public function uploadAndParse(): void
    {
        $this->validate([
            'excelFile' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        try {
            $result = ExcelOrderImportService::parseFile(
                $this->excelFile->getRealPath()
            );
        } catch (Throwable $e) {
            $this->errorMessage = 'Ошибка чтения файла: '.$e->getMessage();

            return;
        }

        $this->fileHeaders = $result['headers'];
        $this->rows = $result['rows'];

        if (empty($this->fileHeaders)) {
            $this->errorMessage = 'Файл пуст или не содержит заголовков';

            return;
        }

        $this->autoDetectColumnMapping();
        $this->step = 2;
    }

    /** Подтверждает маппинг колонок и переходит к предпросмотру обработанных строк. */
    public function confirmMapping(): void
    {
        $this->validate([
            'columnMap.article' => 'required',
            'columnMap.quantity' => 'required',
        ], [
            'columnMap.article.required' => 'Выберите колонку для артикула',
            'columnMap.quantity.required' => 'Выберите колонку для количества',
        ]);

        $this->processRows();
        $this->step = 3;
    }

    /** Сбрасывает выбранное значение кластера при смене маркетплейса. */
    public function updatedGlobalMarketplace(): void
    {
        $this->globalCluster = '';
    }

    /** Генерирует и скачивает PDF-ленту стикеров для обработанных товаров. */
    public function generatePdf()
    {
        if (! $this->globalMarketplace) {
            $this->errorMessage = 'Выберите маркетплейс';

            return;
        }

        $validRows = array_filter($this->processedRows, fn ($r) => $r['item_id'] !== null);

        if (empty($validRows)) {
            $this->errorMessage = 'Нет строк с найденными артикулами';

            return;
        }

        $stickers = [];

        foreach ($validRows as $row) {
            $item = MarketplaceItem::find($row['item_id']);
            $sku = Sku::query()
                ->where('item_id', $item->id)
                ->where('marketplace_id', $this->globalMarketplace)
                ->first();

            if (! $sku) {
                continue;
            }

            $barcode = ($this->globalMarketplace == 1)
                ? MarketplaceApiService::getBarcodeOzonBySku($sku->sku) ?? $sku->sku
                : $sku->sku;

            $cluster = $this->globalCluster;
            $length = mb_strlen($cluster);

            if ($this->globalMarketplace == 1) {
                $fontSizeCluster = ($length > 25) ? 7 : (($length > 18) ? 12 : 14);
            } else {
                $fontSizeCluster = ($length > 25) ? 4 : (($length > 18) ? 7 : 10);
            }

            $productSticker = ProductSticker::query()
                ->where('title', $item->title)
                ->first();

            $stickerData = [
                'barcode' => $barcode,
                'item' => $item,
                'order' => (object) [
                    'order_id' => '',
                    'cluster' => $cluster,
                ],
                'fontSizeCluster' => $fontSizeCluster,
                'seamstressId' => null,
                'cutterId' => null,
                'article' => ($this->globalMarketplace == 2)
                    ? MarketplaceApiService::getItemWbBySku($sku->sku)?->nmID ?? ''
                    : '',
                'color' => $productSticker?->color ?? '',
                'country' => $productSticker?->country ?? '',
            ];

            for ($i = 0; $i < $row['quantity']; $i++) {
                $stickers[] = $stickerData;
            }
        }

        $template = ($this->globalMarketplace == 1)
            ? 'pdf.fbo_ozon_sticker'
            : 'pdf.fbo_wb_sticker';

        $pdf = Pdf::loadView($template, ['stickers' => $stickers]);
        $pdf->setPaper('A4', 'portrait');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'stickers.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    /** Возвращает пользователя к указанному шагу мастера импорта. */
    public function goToStep(int $step): void
    {
        $this->errorMessage = '';
        $this->step = $step;
    }

    /** Автоматически определяет маппинг колонок по названиям заголовков файла. */
    private function autoDetectColumnMapping(): void
    {
        $exactPatterns = [
            'article' => ['артикул', 'article', 'арт.', 'арт', 'sku', 'баркод', 'артикул поставщика'],
            'quantity' => ['количество', 'кол-во', 'кол.', 'кол', 'qty', 'quantity', 'шт', 'шт.', 'количество, шт.'],
        ];

        $containsPatterns = [
            'article' => ['артикул', 'article', 'sku', 'баркод'],
            'quantity' => ['кол', 'qty', 'шт'],
        ];

        foreach ($this->fileHeaders as $index => $header) {
            $h = mb_strtolower(trim((string) $header));
            foreach ($exactPatterns as $field => $keywords) {
                if (empty($this->columnMap[$field]) && in_array($h, $keywords)) {
                    $this->columnMap[$field] = (string) $index;
                }
            }
            if (empty($this->columnMap['article'])) {
                foreach ($containsPatterns['article'] as $keyword) {
                    if (str_contains($h, $keyword)) {
                        $this->columnMap['article'] = (string) $index;
                        break;
                    }
                }
            }
        }
    }

    /** Обрабатывает строки из файла, сопоставляя артикулы с товарами в базе данных. */
    private function processRows(): void
    {
        $this->processedRows = [];

        foreach ($this->rows as $rowIndex => $row) {
            $articleCol = (int) $this->columnMap['article'];
            $qtyCol = (int) $this->columnMap['quantity'];

            $articleValue = trim((string) ($row[$articleCol] ?? ''));
            $qtyValue = (int) ($row[$qtyCol] ?? 1);

            $item = MarketplaceItem::query()
                ->where('article', $articleValue)
                ->first();

            $this->processedRows[] = [
                'index' => $rowIndex,
                'article_raw' => $articleValue,
                'quantity' => max($qtyValue, 1),
                'item_id' => $item?->id,
                'item_title' => $item
                    ? "{$item->title} {$item->width}x{$item->height}"
                    : null,
                'error' => $item ? null : 'Товар не найден по артикулу: '.$articleValue,
            ];
        }
    }

    /** Отображает view мастера импорта Excel-файла для генерации стикеров. */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.sticker-tape-import');
    }
}
