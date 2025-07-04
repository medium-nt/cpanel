<div>
    <div class="row">
        <div class="col-md-9">
            <input type="text"
                   wire:model.defer="orderId"
                   wire:keydown.enter="addOrderToSupply"
                   class="form-control mb-3"
                   placeholder="Введите номер заказа">
        </div>
        <div class="col-md-3">
            <button wire:click="addOrderToSupply" class="btn btn-primary w-100">Добавить заказ</button>
        </div>
    </div>

    @if (!empty($matchingOrders))
        <div class="row mt-3">
            <div class="col-md-9 mb-1">
                <select wire:model="selectedOrderId" class="form-control">
                    <option value="">Выберите заказ...</option>
                    @foreach ($matchingOrders as $order)
                        <option value="{{ $order->id }}">
                            <b>{{ $order->order_id }} </b> — {{ $order->items[0]->item->title }} {{ $order->items[0]->item->width }}х{{ $order->items[0]->item->height }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button wire:click="confirmSelectedOrder" class="btn btn-success w-100">Подтвердить</button>
            </div>
        </div>
    @endif

    @if ($message)
        <div class="mt-2 text-{{ $messageType === 'error' ? 'danger' : ($messageType === 'success' ? 'success' : 'info') }}">
            {{ $message }}
        </div>
    @endif
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Livewire !== 'undefined') {
                Livewire.on('clearMessage', () => {
                    setTimeout(() => {
                        @this.call('resetMessage');
                    }, 3000);
                });
            } else {
                console.warn('Livewire не загружен');
            }
        });
    </script>
@endpush

