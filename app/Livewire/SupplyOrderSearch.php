<?php

namespace App\Livewire;

use App\Services\MarketplaceApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;
use App\Models\MarketplaceOrder;
use Livewire\Attributes\On;

class SupplyOrderSearch extends Component
{
    public $orderId;
    public $supply;
    public $message;
    public $messageType = 'success, error';

    public $matchingOrders = [];
    public $selectedOrderId = null;

    protected $rules = [
        'orderId' => 'required',
    ];

    public function mount($supply)
    {
        $this->supply = $supply;
    }

    public function addOrderToSupply(): void
    {
        $this->validate();

        if (mb_strlen(trim($this->orderId)) < 4) {
            $this->message = 'Введите минимум 4 символа для поиска.';
            $this->messageType = 'error';
            $this->dispatch('orderError');
            $this->dispatch('clearMessage');
            return;
        }

        if (mb_strlen(trim($this->orderId)) == 15) {
            $this->orderId = MarketplaceApiService::getOzonPostingNumberByBarcode($this->orderId);
        }

        $matches = MarketplaceOrder::query()->where(function ($query) {
            $query->where('order_id', 'like', '%' . $this->orderId . '%')
                ->orWhere('part_b', $this->orderId)
                ->orWhere('barcode', $this->orderId);
        })->where('status', 6)
            ->where('fulfillment_type', 'FBS')
            ->where('marketplace_id', $this->supply->marketplace_id)
            ->get();

        if ($matches->isEmpty()) {
            $this->message = 'Нет такого заказа.';
            $this->messageType = 'error';
            $this->dispatch('orderError');
            $this->dispatch('clearMessage');
            return;
        }

        if ($matches->count() > 1) {
            $this->matchingOrders = $matches;
            $this->message = 'Найдено несколько заказов. Выберите нужный.';
            $this->messageType = 'info';
            $this->dispatch('orderError');
            return;
        }

        $this->attachOrder($matches->first());
        $this->dispatch('focusOrderInput');
    }


    public function updatedMessage(): void
    {
        if ($this->message) {
            $this->dispatch('clear-message');
        }
    }

    #[On('resetMessage')]
    public function resetMessage(): void
    {
        $this->message = null;
    }

    public function render(): View
    {
        return view('livewire.supply-order-search');
    }

    public function confirmSelectedOrder(): void
    {
        $order = MarketplaceOrder::find($this->selectedOrderId);

        if (!$order) {
            $this->message = 'Выбранный заказ не найден.';
            $this->messageType = 'error';
            $this->dispatch('orderError');
            $this->dispatch('clearMessage');
            return;
        }

        $this->attachOrder($order);
    }

    protected function attachOrder(MarketplaceOrder $order)
    {
        if ($order->supply_id === $this->supply->id) {
            $this->message = 'Уже добавлен.';
            $this->messageType = 'error';
            $this->dispatch('orderError');
            $this->dispatch('clearMessage');
            return;
        }

        $order->supply_id = $this->supply->id;
        $order->marketplace_status = MarketplaceApiService::getStatusOrder($order);
        $order->save();

        Log::channel('erp')->notice('Заказ №' . $order->order_id . ' успешно добавлен в поставку.');

        $this->orderId = '';
        $this->selectedOrderId = null;
        $this->matchingOrders = [];
        $this->message = 'Добавлен!';
        $this->messageType = 'success';
        $this->dispatch('orderAdded');
        $this->dispatch('clearMessage');
    }

}
