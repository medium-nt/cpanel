<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\MarketplaceOrder;

class SupplyOrderList extends Component
{
    public $supplyId;

    protected $listeners = ['orderAdded' => '$refresh'];

    public function mount($supplyId)
    {
        $this->supplyId = $supplyId;
    }

    public function removeOrder($orderId)
    {
        $order = MarketplaceOrder::find($orderId);

        if ($order && $order->supply_id === $this->supplyId) {
            $order->supply_id = null;
            $order->save();
        }

        $this->dispatch('orderRemoved'); // можно использовать для уведомлений
    }

    public function render()
    {
        $supply_orders = MarketplaceOrder::with('items.item')
            ->where('supply_id', $this->supplyId)
            ->get();

        return view('livewire.supply-order-list', compact('supply_orders'));
    }
}
