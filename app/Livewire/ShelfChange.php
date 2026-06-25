<?php

namespace App\Livewire;

use App\Models\MarketplaceOrderItem;
use App\Models\Shelf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class ShelfChange extends Component
{
    /** выбранная полка в выпадашке */
    public ?int $selectedShelfId = null;

    /** код из сканера */
    public string $scanCode = '';

    /** служебное */
    public string $statusMessage = '';

    public string $statusType = 'info'; // ok|error|warn|info

    public string $statusClass = 'alert-secondary';

    /** отсканированные товары */
    public array $scannedItems = [];

    /** справочник полок */
    public Collection $shelves;

    /** Загружает справочник полок при инициализации компонента. */
    public function mount(): void
    {
        $this->shelves = Shelf::query()
            ->orderBy('title')
            ->get();
    }

    /** Отображает view компонента для смены полок. */
    public function render(): View
    {
        return view('livewire.shelf-change');
    }

    /** Обновляет статус при изменении выбранной полки. */
    public function updatedSelectedShelfId(): void
    {
        $this->setStatus(
            $this->selectedShelfId
                ? 'Полка выбрана'
                : 'Полка не выбрана',
            $this->selectedShelfId ? 'ok' : 'warn'
        );
    }

    /** Сканирует штрихкод товара и добавляет в список для перемещения. */
    public function handleScan(): void
    {
        $code = trim($this->scanCode);
        $this->scanCode = '';

        if ($code === '') {
            return;
        }

        $item = MarketplaceOrderItem::where('storage_barcode', $code)->first();

        if (! $item) {
            $this->setStatus("Неизвестный штрихкод: $code. Разрешено сканировать только стикеры хранения.", 'error');

            return;
        }

        if ($item->status !== 11) {
            $this->setStatus("Товар со штрихкодом: $code не находится на хранении! Разрешено перемещать только товары находящиеся на складе.", 'error');

            return;
        }

        if (isset($this->scannedItems[$item->id])) {
            $this->setStatus("Товар со штрихкодом $code уже добавлен в список", 'warn');

            return;
        }

        $this->scannedItems[$item->id] = [
            'id' => $item->id,
            'storage_barcode' => $item->storage_barcode,
            'item_title' => $item->item->title,
            'item_width' => $item->item->width,
            'item_height' => $item->item->height,
            'current_shelf_id' => $item->shelf_id,
            'current_shelf_title' => $item->shelf?->title,
            'new_shelf_id' => $this->selectedShelfId,
        ];

        $currentShelf = $this->scannedItems[$item->id]['current_shelf_title'] ?? 'без полки';
        $newShelf = $this->shelves->firstWhere('id', $this->selectedShelfId)?->title;

        $this->setStatus("Товар со штрихкодом $code добавлен.", 'ok');
    }

    /** Удаляет товар из списка отсканированных для перемещения. */
    public function removeFromList(int $itemId): void
    {
        if (! isset($this->scannedItems[$itemId])) {
            $this->setStatus("Товар #$itemId не найден в списке", 'error');

            return;
        }

        $barcode = $this->scannedItems[$itemId]['storage_barcode'];
        unset($this->scannedItems[$itemId]);

        $this->setStatus("Товар со штрихкодом $barcode удален из списка", 'ok');
    }

    /** Сохраняет изменения — перемещает отсканированные товары на выбранную полку. */
    public function saveChanges(): void
    {
        if (empty($this->scannedItems)) {
            $this->setStatus('Список товаров пуст. Нечего сохранять.', 'warn');

            return;
        }

        if (! $this->selectedShelfId) {
            $this->setStatus('Полка не выбрана.', 'error');

            return;
        }

        $count = 0;

        foreach ($this->scannedItems as $itemData) {
            $orderItem = MarketplaceOrderItem::find($itemData['id']);

            if ($orderItem && $orderItem->status === 11) {
                $orderItem->shelf_id = $this->selectedShelfId;
                $orderItem->save();
                $count++;
            }
        }

        $scannedCount = count($this->scannedItems);

        Log::channel('items')->info('Перемещение товаров на полку', [
            'moved_count' => $count,
            'scanned_count' => $scannedCount,
            'to_shelf_id' => $this->selectedShelfId,
            'item_ids' => array_column($this->scannedItems, 'id'),
            'moved_by' => auth()->id(),
        ]);

        $this->scannedItems = [];

        $this->setStatus("Успешно перемещено $count из $scannedCount товаров на полку.", 'ok');
    }

    /** Устанавливает статусное сообщение и соответствующий CSS-класс. */
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
