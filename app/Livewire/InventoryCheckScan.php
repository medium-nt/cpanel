<?php

namespace App\Livewire;

use App\Models\MarketplaceOrderItem;
use Illuminate\View\View;
use Livewire\Component;
use App\Models\InventoryCheck;
use App\Models\InventoryCheckItem;
use App\Models\Shelf;

class InventoryCheckScan extends Component
{
    public InventoryCheck $inventory;

    /** выбранная полка в выпадашке */
    public ?int $selectedShelfId = null;

    /** код из сканера */
    public string $scanCode = '';

    /** служебное */
    public string $statusMessage = '';
    public string $statusType = 'info'; // ok|error|warn|info
    public string $statusClass = 'alert-secondary';
    public int $totalItems = 0;
    public int $foundItems = 0;

    /** справочники */
    public $shelves;
    protected string $itemBarcodeColumn = 'storage_barcode';

    public function mount(InventoryCheck $inventoryCheck): void
    {
        $this->inventory = $inventoryCheck->fresh();
        $this->shelves = Shelf::query()
            ->whereIn('id', $this->inventory->items()->pluck('expected_shelf_id'))
            ->get();
        $this->refreshCounters();
    }

    public function render(): View
    {
        $foundRows = InventoryCheckItem::with([
            'marketplaceOrderItem',
            'expectedShelf:id,title',
            'foundedShelf:id,title',
        ])
            ->where('inventory_check_id', $this->inventory->id)
            ->where('is_found', true)
            ->orderByDesc('updated_at')
            ->get();

        $notFoundRows = collect();

        if ($this->inventory->status === 'closed') {
            $notFoundRows = InventoryCheckItem::with([
                'marketplaceOrderItem',
                'expectedShelf:id,title',
            ])
                ->where('inventory_check_id', $this->inventory->id)
                ->where('is_found', false)
                ->orderBy('expected_shelf_id')
                ->get();
        }

        return view('livewire.inventory-check-scan', [
            'foundRows' => $foundRows,
            'notFoundRows' => $notFoundRows,
        ]);
    }

    public function updatedSelectedShelfId(): void
    {
        $this->setStatus(
            $this->selectedShelfId
                ? 'Полка выбрана'
                : 'Полка не выбрана',
            $this->selectedShelfId ? 'ok' : 'warn'
        );
    }

    public function handleScan(): void
    {
        $code = trim($this->scanCode);
        $this->scanCode = '';

        if ($code === '')
            return;

        if ($this->inventory->status === 'closed') {
            $this->setStatus('Инвентаризация закрыта. Сканирование запрещено.', 'error');
            return;
        }

        if (!$this->selectedShelfId) {
            $this->setStatus('Сначала выбери полку в выпадающем списке.', 'error');
            return;
        }

        $item = MarketplaceOrderItem::where($this->itemBarcodeColumn, $code)->first();

        if (!$item) {
            $this->setStatus("Неизвестный штрихкод: $code. Разрешено сканировать только стикеры хранения.", 'error');
            return;
        }

        /** строка инвентаризации для этого товара */
        $row = InventoryCheckItem::where('inventory_check_id', $this->inventory->id)
            ->where('marketplace_order_item_id', $item->id)
            ->first();

        if (!$row) {
            $this->setStatus(
                "Товар со штрихкодом $code
                не включён в данную инвентаризацию! Он должен храниться на полке
                \"{$item->shelf->title}\" ",
                'error'
            );
            return;
        }

        if ($row->is_found) {
            $this->setStatus("Товар со штрихкодом $code уже найден ранее", 'warn');
            return;
        }

        $row->is_found = true;
        $row->founded_shelf_id = $this->selectedShelfId;
        $row->save();

        $this->refreshCounters();

        $isWrongShelf = $row->expected_shelf_id && $row->expected_shelf_id !== $row->founded_shelf_id;
        $expectedCode = optional($row->expectedShelf)->title;
        $currentCode = optional($row->foundedShelf)->title;

        $msg = "Товар со штрихкодом $code найден на полке: $currentCode";

        $this->setStatus(
            $msg . ($isWrongShelf ? " (по системе на полке: $expectedCode)" : ''),
            $isWrongShelf ? 'warn' : 'ok'
        );
    }

    public function closeCheck(): void
    {
        if ($this->inventory->status === 'closed')
            return;

        $this->changeShelf();
        $this->setStatusLost();

        $this->inventory->status = 'closed';
        $this->inventory->finished_at = now();
        $this->inventory->save();

        $this->setStatus('Инвентаризация закрыта', 'ok');
    }

    protected function changeShelf(): void
    {
        //  Все товары не на своих полках меняем на новую полку, где нашли товар
        $inventoryItems = InventoryCheckItem::query()
            ->where('inventory_check_id', $this->inventory->id)
            ->where('is_found', true)
            ->whereNotNull('founded_shelf_id')
            ->where('expected_shelf_id', '!=', 'founded_shelf_id')
            ->get();

        /** @var InventoryCheckItem $inventoryItems */
        foreach ($inventoryItems as $item) {
            $item->marketplaceOrderItem->shelf_id = $item->founded_shelf_id;
            $item->marketplaceOrderItem->save();
        }
    }

    protected function setStatusLost(): void
    {
        //  Все которые не найдены - меняет статус на "утерян"
        $notFoundItems = InventoryCheckItem::query()
            ->where('inventory_check_id', $this->inventory->id)
            ->where('is_found', false)
            ->get();

        /** @var InventoryCheckItem $notFoundItems */
        foreach ($notFoundItems as $item) {
            $item->marketplaceOrderItem->status = 14;
            $item->marketplaceOrderItem->save();
        }
    }

    protected function refreshCounters(): void
    {
        $this->totalItems = InventoryCheckItem::query()
            ->where('inventory_check_id', $this->inventory->id)
            ->count();

        $this->foundItems = InventoryCheckItem::query()
            ->where('inventory_check_id', $this->inventory->id)
            ->where('is_found', true)->count();
    }

    public function unmarkFound(int $rowId): void
    {
        if ($this->inventory->status === 'closed') {
            $this->setStatus('Инвентаризация закрыта. Правки запрещены.', 'error');
            return;
        }

        $row = InventoryCheckItem::query()
            ->where('inventory_check_id', $this->inventory->id)
            ->where('id', $rowId)
            ->first();

        if (!$row) {
            $this->setStatus("Строка инвентаризации #$rowId не найдена", 'error');
            return;
        }

        if (!$row->is_found) {
            $this->setStatus('Эта позиция уже была снята с “найдено”.', 'warn');
            return;
        }

        $row->is_found = false;
        $row->founded_shelf_id = null;
        $row->save();

        $this->refreshCounters();
        $this->setStatus('Товар удален из найденных.', 'ok');
    }

    protected function setStatus(string $message, string $type = 'info'): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;

        $map = [
            'ok' => 'alert-success',
            'warn' => 'alert-warning',
            'error' => 'alert-danger',
            'info' => 'alert-secondary',
        ];
        $this->statusClass = $map[$type] ?? 'alert-secondary';
    }
}

