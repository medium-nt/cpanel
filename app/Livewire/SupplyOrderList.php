<?php

namespace App\Livewire;

use App\Models\MarketplaceSupply;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Models\MarketplaceOrder;

class SupplyOrderList extends Component
{
    public $supplyId;

    protected $listeners = [
        'removeOrder' => 'removeOrder',
        'orderAdded' => '$refresh',
    ];

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

            Log::channel('erp')->notice('    Заказ №'.$order->order_id.' успешно удален из поставки.');
        }

        $this->dispatch('orderRemoved');
    }

    public function render()
    {
        $supply_orders = MarketplaceOrder::with('items.item')
            ->where('supply_id', $this->supplyId)
            ->get();

        $marketplaceSupply = MarketplaceSupply::query()->find($this->supplyId);

        $totalReady = MarketplaceOrder::query()
            ->where('status', 6)
            ->where('fulfillment_type', 'FBS')
            ->where('marketplace_id', $marketplaceSupply->marketplace_id)
            ->count();

        $totalItems = $supply_orders->sum(function ($order) {
            return $order->items->count();
        });

        $status = $marketplaceSupply->status;

        return view('livewire.supply-order-list', compact('supply_orders', 'totalItems', 'totalReady', 'status'));
    }
}
