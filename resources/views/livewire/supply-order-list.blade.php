<div>
    {{-- Смартфон-карточки --}}
    <div class="row only-on-smartphone">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                @if($status == 0)
                Всего готово товаров: <strong>{{ $totalReady }}</strong> <br>
                @endif
                Добавлено товаров: <strong>{{ $totalItems }}</strong>
                </div>
            </div>
        </div>
        @foreach ($supply_orders as $order)
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <b>{{ $order->order_id }}</b> {!! $order->marketplace_status_label !!}
                        <div class="my-3">
                            @php $firstItem = $order->items->first(); @endphp
                            @if($firstItem && $firstItem->item)
                                {{ $firstItem->item->title }} {{ $firstItem->item->width }}
                                х{{ $firstItem->item->height }}
                            @endif
                            {{--                            {{ $order->items[0]->item->title }} {{ $order->items[0]->item->width }}х{{ $order->items[0]->item->height }}--}}
                        </div>
                        @if($status == 0)
                        <div class="btn-group" role="group">
                            <button
                                onclick="confirmRemove({{ $order->id }})"
                                class="btn btn-danger">
                                <i class="fas fa-trash mr-1"></i> Убрать из поставки
                            </button>

                            <script>
                                function confirmRemove(orderId) {
                                    if (confirm('Вы уверены что хотите убрать данный заказ из этой поставки?')) {
                                        Livewire.dispatch('removeOrder', { orderId: orderId });
                                    }
                                }
                            </script>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Десктоп-таблица --}}
    <div class="only-on-desktop">
        <div class="card">
            <div class="card-body">
                @if($status == 0)
                Всего готово товаров: <strong>{{ $totalReady }}</strong> <br>
                @endif
                Добавлено товаров: <strong>{{ $totalItems }}</strong>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th>Статус</th>
                            <th>Номер заказа</th>
                            <th>Товар</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($supply_orders as $order)
                            <tr>
                                <td>{!! $order->marketplace_status_label !!}</td>
                                <td>{{ $order->order_id }} <b>{{ $order->part_b ? "({$order->part_b})" : '' }}</b></td>
                                <td>
                                    @php $firstItem = $order->items->first(); @endphp
                                    @if($firstItem && $firstItem->item)
                                        {{ $firstItem->item->title }} {{ $firstItem->item->width }}
                                        х{{ $firstItem->item->height }}
                                    @endif
                                    {{--                                    {{ $order->items[0]->item->title }} {{ $order->items[0]->item->width }}х{{ $order->items[0]->item->height }}--}}
                                </td>
                                <td style="width: 100px">
                                @if($status == 0)
                                    <button
                                        onclick="confirmRemove({{ $order->id }})"
                                        class="btn btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    <script>
                                        function confirmRemove(orderId) {
                                            if (confirm('Вы уверены что хотите убрать данный заказ из этой поставки?')) {
                                                Livewire.dispatch('removeOrder', { orderId: orderId });
                                            }
                                        }
                                    </script>
                                @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
