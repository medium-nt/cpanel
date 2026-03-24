<?php

namespace App\Livewire;

use App\Models\MarketplaceOrderItem;
use App\Models\Shelf;
use App\Models\StatusMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class StatusChangeScan extends Component
{
    public int $fromStatus;

    public int $toStatus;

    public string $pageTitle;

    public string $scanCode = '';

    public array $scannedItems = [];

    public Collection $items;

    public string $statusMessage = '';

    public string $statusClass = 'alert-secondary';

    public int $selectedShelfId = 0;

    public Collection $shelves;

    public function mount(): void
    {
        $this->fromStatus = (int) request('from', 0);
        $this->toStatus = (int) request('to', 0);
        $this->pageTitle = request('title', 'Сканирование товаров');

        $this->loadItems();

        if ($this->isStorageScenario()) {
            $this->loadShelves();
        }
    }

    public function render(): View
    {
        return view('livewire.status-change-scan');
    }

    protected function loadItems(): void
    {
        $this->items = MarketplaceOrderItem::with(['item'])
            ->where('status', $this->fromStatus)
            ->get();
    }

    protected function isStorageScenario(): bool
    {
        return $this->fromStatus === 18 && $this->toStatus === 11;
    }

    protected function loadShelves(): void
    {
        $this->shelves = Shelf::orderBy('title')->get();
    }

    public function handleScan(): void
    {
        $code = trim($this->scanCode);
        $this->scanCode = '';

        if ($code === '') {
            return;
        }

        $item = MarketplaceOrderItem::with(['item'])
            ->where('storage_barcode', $code)
            ->where('status', $this->fromStatus)
            ->first();

        if (! $item) {
            $this->setStatus("Не найден товар со штрихкодом хранения: $code", 'error');
            $this->dispatch('scanError');

            return;
        }

        if (isset($this->scannedItems[$item->id])) {
            $this->setStatus("Товар уже добавлен в список: {$item->item->title} {$item->item->width}x{$item->item->height}", 'warn');
            $this->dispatch('scanError');

            return;
        }

        $this->scannedItems[$item->id] = [
            'id' => $item->id,
            'storage_barcode' => $item->storage_barcode,
            'item_title' => $item->item->title,
            'item_width' => $item->item->width,
            'item_height' => $item->item->height,
        ];

        $this->setStatus("Добавлен: {$item->item->title} {$item->item->width}x{$item->item->height}", 'ok');
        $this->dispatch('scanSuccess');
    }

    public function removeFromList(int $itemId): void
    {
        if (! isset($this->scannedItems[$itemId])) {
            $this->setStatus("Товар #$itemId не найден в списке", 'error');

            return;
        }

        $title = $this->scannedItems[$itemId]['item_title'];
        unset($this->scannedItems[$itemId]);

        $this->setStatus("Удален из списка: $title", 'ok');
    }

    public function complete(): void
    {
        if (empty($this->scannedItems)) {
            $this->setStatus('Список товаров пуст. Нечего сохранять.', 'warn');

            return;
        }

        // Валидация выбора полки для сценария размещения на склад
        if ($this->isStorageScenario() && $this->selectedShelfId === 0) {
            $this->setStatus('Выберите полку для размещения товаров', 'warn');

            return;
        }

        $count = 0;
        $changedItemIds = [];

        foreach ($this->scannedItems as $itemData) {
            $orderItem = MarketplaceOrderItem::find($itemData['id']);

            if ($orderItem && $orderItem->status === $this->fromStatus) {
                // Сохраняем shelf_id только для сценария размещения
                if ($this->isStorageScenario()) {
                    $orderItem->shelf_id = $this->selectedShelfId;
                }
                $orderItem->status = $this->toStatus;
                $orderItem->save();
                $changedItemIds[] = $orderItem->id;
                $count++;
            }
        }

        // Единый лог для всех товаров
        if (! empty($changedItemIds)) {
            $toStatusName = StatusMovement::STATUSES[$this->toStatus] ?? "статус {$this->toStatus}";
            $itemsList = implode(', ', $changedItemIds);

            Log::channel('items')->info(
                'Кладовщик '.auth()->user()->name.
                " на странице '{$this->pageTitle}' отсканировал товары: {$itemsList}".
                " (статус: {$toStatusName})"
            );
        }

        $scannedCount = count($this->scannedItems);
        $this->scannedItems = [];
        $this->loadItems();

        $this->setStatus("Успешно изменен статус у $count из $scannedCount товаров.", 'ok');
    }

    public function getScannedCountProperty(): int
    {
        return count($this->scannedItems);
    }

    public function getRemainingCountProperty(): int
    {
        return $this->items->count() - $this->getScannedCountProperty();
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
