<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\MarketplaceOrder;

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
            $this->dispatch('clearMessage');
            return;
        }

        $matches = MarketplaceOrder::where(function ($query) {
            $query->where('order_id', 'like', '%' . $this->orderId . '%')
                ->where('status', 6)
                ->where('marketplace_id', $this->supply->marketplace_id)
                ->orWhere('part_b', $this->orderId)
                ->orWhere('barcode', $this->orderId);
        })->get();

        if ($matches->isEmpty()) {
            $this->message = 'Нет такого заказа.';
            $this->messageType = 'error';
            $this->dispatch('clearMessage');
            return;
        }

        if ($matches->count() > 1) {
            $this->matchingOrders = $matches;
            $this->message = 'Найдено несколько заказов. Выберите нужный.';
            $this->messageType = 'info';
            return;
        }

        $this->attachOrder($matches->first());
    }


    public function updatedMessage()
    {
        if ($this->message) {
            $this->dispatchBrowserEvent('clear-message');
        }
    }

    #[On('resetMessage')]
    public function resetMessage()
    {
        $this->message = null;
    }

    public function render()
    {
        return view('livewire.supply-order-search');
    }

    public function confirmSelectedOrder()
    {
        $order = MarketplaceOrder::find($this->selectedOrderId);

        if (!$order) {
            $this->message = 'Выбранный заказ не найден.';
            $this->messageType = 'error';
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
            $this->dispatch('clearMessage');
            return;
        }

        $order->supply_id = $this->supply->id;
        $order->save();

        $this->orderId = '';
        $this->selectedOrderId = null;
        $this->matchingOrders = [];
        $this->message = 'Добавлен!';
        $this->messageType = 'success';
        $this->dispatch('orderAdded');
        $this->dispatch('clearMessage');
    }

}
