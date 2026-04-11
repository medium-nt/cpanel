<?php

namespace App\Livewire;

use App\Models\MarketplaceItem;
use App\Models\MarketplaceWarehouse;
use App\Services\ExcelOrderImportService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class ExcelOrderImport extends Component
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

    public int $createdCount = 0;

    public string $errorMessage = '';

    public string $globalCluster = '';

    public int $globalMarketplace = 0;

    /** @var array<int, array{id: int, title: string}> */
    public array $allItems = [];

    /** @var array<int, array<int, string>> Склады, сгруппированные по marketplace_id */
    public array $warehouses = [];

    public function mount(): void
    {
        $this->allItems = MarketplaceItem::query()
            ->select(['id', 'title', 'width', 'height'])
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'title' => "{$item->title} {$item->width}x{$item->height}",
            ])
            ->toArray();

        $this->warehouses = MarketplaceWarehouse::query()
            ->orderBy('name')
            ->get()
            ->groupBy('marketplace_id')
            ->map(fn ($group) => $group->pluck('name', 'name')->toArray())
            ->toArray();
    }

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

    public function updateRowQuantity(int $rowIndex, int $value): void
    {
        if (isset($this->processedRows[$rowIndex])) {
            $this->processedRows[$rowIndex]['quantity'] = max($value, 1);
        }
    }

    public function updatedGlobalMarketplace(): void
    {
        $this->globalCluster = '';
    }

    public function updateRowItem(int $rowIndex, int $itemId): void
    {
        if (! isset($this->processedRows[$rowIndex])) {
            return;
        }

        $item = MarketplaceItem::query()->find($itemId);
        $this->processedRows[$rowIndex]['item_id'] = $itemId;
        $this->processedRows[$rowIndex]['item_title'] = $item
            ? "{$item->title} {$item->width}x{$item->height}"
            : null;
        $this->processedRows[$rowIndex]['error'] = null;
    }

    public function removeRow(int $rowIndex): void
    {
        unset($this->processedRows[$rowIndex]);
        $this->processedRows = array_values($this->processedRows);
    }

    public function save(): void
    {
        if (! $this->globalMarketplace || ! $this->globalCluster) {
            $this->errorMessage = 'Выберите маркетплейс и склад перед сохранением.';

            return;
        }

        $validRows = array_filter($this->processedRows, fn ($r) => $r['item_id'] !== null);
        $errorRows = array_filter($this->processedRows, fn ($r) => $r['item_id'] === null);

        if (count($errorRows) > 0) {
            $this->errorMessage = 'Есть строки без товара ('.count($errorRows).' шт.). Удалите их или выберите товар вручную.';

            return;
        }

        if (empty($validRows)) {
            $this->errorMessage = 'Нет строк для сохранения';

            return;
        }

        try {
            $this->createdCount = ExcelOrderImportService::createOrders(
                array_values($validRows),
                $this->globalMarketplace,
                $this->globalCluster,
            );
            $this->step = 4;
        } catch (Throwable $e) {
            Log::channel('orders')->error('Excel import save error: '.$e->getMessage());
            $this->errorMessage = 'Ошибка при сохранении: '.$e->getMessage();
        }
    }

    public function goToStep(int $step): void
    {
        $this->errorMessage = '';
        $this->step = $step;
    }

    private function autoDetectColumnMapping(): void
    {
        $exactPatterns = [
            'article' => ['артикул', 'article', 'арт.', 'арт', 'sku'],
            'quantity' => ['количество', 'кол-во', 'кол.', 'кол', 'qty', 'quantity', 'шт', 'шт.'],
        ];

        $containsPatterns = [
            'article' => ['артикул', 'article', 'sku'],
            'quantity' => ['кол', 'qty'],
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

    private function processRows(): void
    {
        $this->processedRows = [];

        foreach ($this->rows as $rowIndex => $row) {
            $articleCol = (int) $this->columnMap['article'];
            $qtyCol = (int) $this->columnMap['quantity'];

            $articleValue = trim((string) ($row[$articleCol] ?? ''));
            $qtyValue = (int) ($row[$qtyCol] ?? 1);

            $match = ExcelOrderImportService::matchRow($articleValue);

            $this->processedRows[] = [
                'index' => $rowIndex,
                'article_raw' => $articleValue,
                'quantity' => max($qtyValue, 1),
                'item_id' => $match['item_id'],
                'item_title' => $match['item_title'],
                'error' => $match['error'],
            ];
        }
    }

    public function getMatchedCountProperty(): int
    {
        return count(array_filter($this->processedRows, fn ($r) => $r['item_id'] !== null));
    }

    public function getUnmatchedCountProperty(): int
    {
        return count(array_filter($this->processedRows, fn ($r) => $r['item_id'] === null));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.excel-order-import');
    }
}
