<div>
    {{-- Смартфон-карточки --}}
    <div class="row only-on-smartphone">
        @foreach ($supply_orders as $order)
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <b>{{ $order->order_id }}</b>
                        <div class="my-3">
                            {{ $order->items[0]->item->title }} {{ $order->items[0]->item->width }}х{{ $order->items[0]->item->height }}
                        </div>
                        <div class="btn-group" role="group">
                            <button wire:click="removeOrder({{ $order->id }})"
                                    onclick="return confirm('Вы уверены что хотите убрать данный заказ из этой поставки?')"
                                    class="btn btn-danger">
                                <i class="fas fa-trash mr-1"></i> Убрать из поставки
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Десктоп-таблица --}}
    <div class="card only-on-desktop">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="thead-dark">
                    <tr>
                        <th>Номер заказа</th>
                        <th>Товар</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($supply_orders as $order)
                        <tr>
                            <td>{{ $order->order_id }}</td>
                            <td>{{ $order->items[0]->item->title }} {{ $order->items[0]->item->width }}х{{ $order->items[0]->item->height }}</td>
                            <td style="width: 100px">
                                <button wire:click="removeOrder({{ $order->id }})"
                                        onclick="return confirm('Вы уверены что хотите убрать данный заказ из этой поставки?')"
                                        class="btn btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
