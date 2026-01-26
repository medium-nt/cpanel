<?php

namespace App\Livewire;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceSupply;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Component;

class SupplyOrderList extends Component
{
    public $supplyId;

    protected $listeners = [
        'removeOrder' => 'removeOrder',
        'orderAdded' => '$refresh',
    ];

    public function mount($supplyId): void
    {
        $this->supplyId = $supplyId;
    }

    public function removeOrder($orderId): void
    {
        $order = MarketplaceOrder::find($orderId);

        if ($order && $order->supply_id === $this->supplyId) {
            $order->supply_id = null;
            $order->marketplace_status = null;
            $order->save();

            Log::channel('erp')->notice('Заказ №'.$order->order_id.' успешно удален из поставки.');
        }

        $this->dispatch('orderRemoved');
        $this->dispatch('focusOrderInput');
    }

    public function render(): View
    {
        $marketplaceSupply = MarketplaceSupply::query()->find($this->supplyId);

        $readyOrders = MarketplaceOrder::query()
            ->with('items.item')
            ->where('status', 6)
            ->where('fulfillment_type', 'FBS')
            ->where('marketplace_id', $marketplaceSupply->marketplace_id)
            ->where(function ($q) {
                $q->where('supply_id', $this->supplyId)
                    ->orWhereNull('supply_id');
            })
            ->get();

        $supplyOrders = MarketplaceOrder::query()
            ->with('items.item')
            ->where('supply_id', $this->supplyId)
            ->get();

        // объединяем, убираем дубликаты по id и сортируем по updated_at
        $supply_orders = $supplyOrders
            ->merge($readyOrders)
            ->unique('id')
            ->sortByDesc('updated_at')
            ->values();

        $totalItems = $supplyOrders->sum(function ($order) {
            return $order->items->count();
        });

        $totalReady = $readyOrders->count();

        $status = $marketplaceSupply->status;

        return view(
            'livewire.supply-order-list',
            compact('supply_orders', 'totalItems', 'totalReady', 'status')
        );
    }
}
