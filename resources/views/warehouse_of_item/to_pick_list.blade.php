@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    @if($ordersAssembled->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Собранные заказы (на стикеровке)</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Товар</th>
                            <th scope="col">Дата создания</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($ordersAssembled as $orderAssembled)
                            <tr>
                                <td>{{ $orderAssembled->id }}</td>
                                <td>
                                    <a href="{{ route('warehouse_of_item.to_pick', ['order' => $orderAssembled->id]) }}?barcode={{ $orderAssembled->items[0]->storage_barcode }}">
                                        {{ $orderAssembled->items[0]->item->title }}
                                        - {{ $orderAssembled->items[0]->item->width }}
                                        x {{ $orderAssembled->items[0]->item->height }}
                                    </a>
                                </td>
                                <td>{{ now()->parse($orderAssembled->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Заказы на сборку</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="thead-dark">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Товар</th>
                        <th scope="col">Дата создания</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>
                                <a href="{{ route('warehouse_of_item.to_pick', ['order' => $order->id]) }}">
                                    {{ $order->items[0]->item->title }}
                                    - {{ $order->items[0]->item->width }}
                                    x {{ $order->items[0]->item->height }}
                                </a>
                            </td>
                            <td>{{ now()->parse($order->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Pagination --}}
            <x-pagination-component :collection="$orders"/>
        </div>
    </div>
@stop

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
