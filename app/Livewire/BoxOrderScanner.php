<?php

namespace App\Livewire;

use App\Models\MarketplaceOrder;
use App\Models\Sku;
use App\Models\SupplyBox;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Сканер ШК товаров для добавления заказов в короб FBO-поставки.
 */
class BoxOrderScanner extends Component
{
    public SupplyBox $box;

    public string $scanCode = '';

    public string $statusMessage = '';

    public string $statusType = 'info';

    public string $statusClass = 'alert-secondary';

    public function mount(SupplyBox $box): void
    {
        $this->box = $box;
    }

    public function render(): View
    {
        $this->box->load('orders.items.item');

        return view('livewire.box-order-scanner');
    }

    /**
     * Обработка сканирования ШК — поиск заказа и привязка к коробу.
     */
    public function handleScan(): void
    {
        $code = trim($this->scanCode);
        $this->scanCode = '';

        if ($code === '') {
            return;
        }

        $sku = Sku::query()
            ->where('sku', $code)
            ->where('marketplace_id', $this->box->supply->marketplace_id)
            ->first();

        if (! $sku) {
            $this->setStatus("Товар со штрихкодом «{$code}» не найден.", 'error');
            $this->dispatch('scanError');

            return;
        }

        $baseQuery = MarketplaceOrder::query()
            ->where('supply_id', $this->box->marketplace_supply_id)
            ->whereHas('items', function ($query) use ($sku) {
                $query->where('marketplace_item_id', $sku->item_id);
            });

        $order = (clone $baseQuery)
            ->whereNull('box_id')
            ->where('status', 3)
            ->first();

        if (! $order) {
            $hasNotReady = (clone $baseQuery)
                ->whereNull('box_id')
                ->where('status', '!=', 3)
                ->exists();

            if ($hasNotReady) {
                $this->setStatus('Товар есть в поставке, но ещё не готов.', 'warn');
            } elseif ($baseQuery->exists()) {
                $this->setStatus('Все товары с таким ШК уже добавлены в короба.', 'warn');
            } else {
                $this->setStatus('Товар не найден в этой поставке.', 'error');
            }

            $this->dispatch('scanError');

            return;
        }

        $order->update(['box_id' => $this->box->id]);

        $itemTitle = $order->items->first()?->item?->title ?? '';
        $this->setStatus("Добавлен: {$itemTitle}", 'ok');
        $this->dispatch('scanSuccess');
    }

    /**
     * Убрать заказ из короба.
     */
    public function removeOrder(int $orderId): void
    {
        $order = MarketplaceOrder::query()
            ->where('id', $orderId)
            ->where('box_id', $this->box->id)
            ->first();

        if ($order) {
            $order->update(['box_id' => null]);
            $this->setStatus('Заказ убран из короба.', 'ok');
        }
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
