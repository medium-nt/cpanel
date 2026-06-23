<?php

namespace App\Livewire;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Сканер подбора товаров со склада: кладовщик сканирует ШК экземпляра на полке,
 * компонент определяет, годится ли артикул для активного подбора и отсекает «лишние».
 * Без записи в БД — только навигационная таблица в рамках сессии.
 */
class PickupScan extends Component
{
    public string $scanCode = '';

    public string $statusMessage = '';

    public string $statusType = 'info';

    public string $statusClass = 'alert-secondary';

    /** @var array<int, array<string, mixed>> */
    public array $scanned = [];

    public function render(): View
    {
        return view('livewire.pickup-scan');
    }

    /**
     * Обработка сканирования ШК: поиск экземпляра, проверка артикула по активным подборам.
     */
    public function handleScan(): void
    {
        $code = trim($this->scanCode);
        $this->scanCode = '';

        if ($code === '') {
            return;
        }

        $item = MarketplaceOrderItem::query()
            ->where('storage_barcode', $code)
            ->whereIn('status', [11, 13])
            ->with(['item', 'shelf', 'marketplaceOrder'])
            ->first();

        if (! $item) {
            $this->setStatus("Товар со штрихкодом «{$code}» не найден на складе.", 'error');
            $this->dispatch('scanError');

            return;
        }

        if (collect($this->scanned)->contains('item_id', $item->id)) {
            $this->setStatus("Уже отсканирован: {$item->item?->title}.", 'warn');
            $this->dispatch('scanError');

            return;
        }

        $articleId = $item->marketplace_item_id;
        $articleTitle = $item->item?->title ?? '—';

        $needed = MarketplaceOrder::query()
            ->where('status', 13)
            ->whereHas('items', fn ($q) => $q->where('marketplace_item_id', $articleId))
            ->count();

        $scannedOfArticle = collect($this->scanned)
            ->where('article_id', $articleId)
            ->count();

        if ($needed === 0) {
            $this->setStatus("Нет активных заказов на «{$articleTitle}».", 'error');
            $this->dispatch('scanError');

            return;
        }

        if ($scannedOfArticle >= $needed) {
            $this->setStatus("Лишний! На «{$articleTitle}» заказов: {$needed}, уже отсканировано: {$scannedOfArticle}.", 'error');
            $this->dispatch('scanError');

            return;
        }

        array_unshift($this->scanned, [
            'item_id' => $item->id,
            'article_id' => $articleId,
            'article_title' => $articleTitle,
            'order_id' => $item->marketplaceOrder?->order_id,
            'shelf' => $item->shelf?->title,
            'scanned_at' => now()->format('H:i:s'),
        ]);

        $done = $scannedOfArticle + 1;
        $this->setStatus("{$articleTitle} — {$done}/{$needed} (полка: {$item->shelf?->title}).", 'ok');
        $this->dispatch('scanSuccess');
    }

    /**
     * Убрать экземпляр из таблицы отсканированных (только из сессии, без БД).
     */
    public function removeScanned(int $itemId): void
    {
        $this->scanned = collect($this->scanned)
            ->reject(fn (array $row) => $row['item_id'] === $itemId)
            ->values()
            ->all();
    }

    /**
     * Очистить весь список отсканированных.
     */
    public function clearAll(): void
    {
        $this->scanned = [];
        $this->statusMessage = '';
    }

    /**
     * Установить статусное сообщение и класс alert по типу.
     */
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
