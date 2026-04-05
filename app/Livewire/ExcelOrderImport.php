<?php

namespace App\Livewire;

use App\Models\MarketplaceItem;
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
        'sku' => '',
        'quantity' => '',
        'barcode' => '',
        'cluster' => '',
    ];

    public array $rows = [];

    public array $processedRows = [];

    public int $createdCount = 0;

    public string $errorMessage = '';

    public string $globalCluster = '';

    public int $globalMarketplace = 0;

    /** @var array<int, array{id: int, title: string}> */
    public array $allItems = [];

    public const CLUSTERS_OZON = [
        'Алматы', 'Астана', 'Беларусь', 'Воронеж', 'Дальний Восток',
        'Екатеринбург', 'Казань', 'Калининград', 'Краснодар', 'Красноярск',
        'Махачкала', 'Москва, МО и Дальние регионы', 'Невинномысск',
        'Новосибирск', 'Омск', 'Оренбург', 'Пермь', 'Ростов',
        'Самара', 'Санкт-Петербург и СЗО', 'Саратов', 'Тверь', 'Тюмень',
        'Уфа', 'Ярославль',
    ];

    public const CLUSTERS_WB = [
        'Алексин (Тула)', 'Владимир (Воршинское)', 'Волгоград',
        'Екатеринбург (Испытателей)', 'Екатеринбург (Перспективный)',
        'Казань', 'Коледино', 'Котовск', 'Краснодар', 'Невинномысск',
        'Нижний Новгород', 'Новосибирск(Петухова)', 'Рязань',
        'Самара (Новосемейкино)', 'Санкт-Петербург(Уткина Заводь)',
        'Санкт-Петербург(Шушары)', 'Сарапул', 'Электросталь',
    ];

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
            'columnMap.sku' => 'required',
            'columnMap.quantity' => 'required',
        ], [
            'columnMap.sku.required' => 'Выберите колонку для артикула/SKU',
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

    public function updateRowCluster(int $rowIndex, string $value): void
    {
        if (isset($this->processedRows[$rowIndex])) {
            $this->processedRows[$rowIndex]['cluster'] = $value;
        }
    }

    public function updatedGlobalMarketplace(): void
    {
        $this->globalCluster = '';
    }

    public function setClusterForAll(): void
    {
        if ($this->globalCluster === '') {
            return;
        }

        foreach ($this->processedRows as $i => $row) {
            $this->processedRows[$i]['cluster'] = $this->globalCluster;
        }
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
                array_values($validRows)
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
            'sku' => ['артикул', 'sku', 'article', 'арт.', 'арт', 'артикул продавца'],
            'quantity' => ['количество', 'кол-во', 'кол.', 'кол', 'qty', 'quantity', 'шт', 'шт.'],
            'barcode' => ['штрихкод', 'баркод', 'barcode', 'шк', 'штрих-код'],
            'cluster' => ['кластер', 'cluster', 'склад'],
        ];

        $containsPatterns = [
            'sku' => ['артикул', 'sku'],
            'quantity' => ['кол', 'qty'],
            'barcode' => ['баркод', 'штрихкод', 'barcode'],
            'cluster' => ['кластер', 'cluster'],
        ];

        foreach ($this->fileHeaders as $index => $header) {
            $h = mb_strtolower(trim((string) $header));
            foreach ($exactPatterns as $field => $keywords) {
                if (empty($this->columnMap[$field]) && in_array($h, $keywords)) {
                    $this->columnMap[$field] = (string) $index;
                }
            }
            if (empty($this->columnMap['sku']) || empty($this->columnMap['barcode'])) {
                foreach ($containsPatterns as $field => $keywords) {
                    if (empty($this->columnMap[$field])) {
                        foreach ($keywords as $keyword) {
                            if (str_contains($h, $keyword)) {
                                $this->columnMap[$field] = (string) $index;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    private function processRows(): void
    {
        $this->processedRows = [];

        foreach ($this->rows as $rowIndex => $row) {
            $skuCol = (int) $this->columnMap['sku'];
            $qtyCol = (int) $this->columnMap['quantity'];
            $barcodeCol = $this->columnMap['barcode'] !== '' ? (int) $this->columnMap['barcode'] : null;
            $clusterCol = $this->columnMap['cluster'] !== '' ? (int) $this->columnMap['cluster'] : null;

            $skuValue = trim((string) ($row[$skuCol] ?? ''));
            $qtyValue = (int) ($row[$qtyCol] ?? 1);
            $barcodeValue = $barcodeCol !== null ? trim((string) ($row[$barcodeCol] ?? '')) : '';
            $clusterValue = $clusterCol !== null ? trim((string) ($row[$clusterCol] ?? '')) : '';

            $matchValue = $barcodeValue ?: $skuValue;
            $match = ExcelOrderImportService::matchRow($matchValue);

            $this->processedRows[] = [
                'index' => $rowIndex,
                'sku_raw' => $skuValue,
                'barcode_raw' => $barcodeValue,
                'quantity' => max($qtyValue, 1),
                'cluster' => $clusterValue,
                'item_id' => $match['item_id'],
                'marketplace_id' => $match['marketplace_id'],
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
