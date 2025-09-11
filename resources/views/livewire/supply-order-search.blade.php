<div>
    <div class="row" x-data x-init="$refs.orderInput.focus()">
    <div class="col-md-9">
            <input type="text"
                   wire:model.defer="orderId"
                   wire:keydown.enter="addOrderToSupply"
                   x-ref="orderInput"
                   class="form-control mb-3"
                   placeholder="Введите номер заказа">
        </div>
        <div class="col-md-3">
            <button wire:click="addOrderToSupply" class="btn btn-primary w-100">Добавить заказ</button>
        </div>
    </div>

    <audio id="order-added-sound" src="{{ asset('sounds/success.mp3') }}" preload="auto"></audio>
    <audio id="error-sound" src="{{ asset('sounds/error.mp3') }}" preload="auto"></audio>

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

        function playSound(id) {
            const audio = document.getElementById(id);
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(() => {});
            } else {
                console.warn(`Аудио элемент с id="${id}" не найден`);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Livewire !== 'undefined') {

                // Сброс сообщений через 3 секунды
                Livewire.on('clearMessage', () => {
                    setTimeout(() => {
                        @this.call('resetMessage');
                    }, 3000);
                });

                // Звук успешного добавления
                Livewire.on('orderAdded', () =>
                    playSound('order-added-sound')
                );

                // Звук ошибки
                Livewire.on('orderError', () =>
                    playSound('error-sound')
                );

                // обработчик фокуса
                Livewire.on('focusOrderInput', () => {
                    const input = document.querySelector('[x-ref="orderInput"]');
                    if (input) input.focus();
                });

            } else {
                console.warn('Livewire не загружен');
            }
        });
    </script>
@endpush

