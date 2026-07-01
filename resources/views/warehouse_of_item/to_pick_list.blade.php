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

    @if($barcodeNotFound)
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert"
                    aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Штрихкод хранения "{{ $storageBarcode }}" не найден на складе
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Заказы на сборку</h3>
        </div>
        <div class="card-body">
            <div class="row">

                <a href="{{ route('warehouse_of_item.to_pick_list_print') }}"
                   class="btn btn-secondary mb-3 ml-2" target="_blank">
                    <i class="fas fa-print mr-1"></i>
                    Печать списка
                </a>

                <a href="{{ route('warehouse_of_item.pickup_scan') }}"
                   class="btn btn-success mb-3 ml-2 mr-3">
                    <i class="fas fa-barcode mr-1"></i>
                    Сканер подбора
                </a>

                <div class="col-md-4">
                    <form action="{{ route('warehouse_of_item.to_pick_list') }}"
                          method="get">
                        <div class="input-group mb-3">
                            <input type="text"
                                   name="storage_barcode"
                                   class="form-control"
                                   placeholder="Сканируйте штрихкод хранения"
                                   autofocus
                                   autocomplete="off"
                                   value="{{ $storageBarcode ?? '' }}">
                            @isset($storageBarcode)
                                <div class="input-group-append">
                                    <a href="{{ route('warehouse_of_item.to_pick_list') }}"
                                       class="btn btn-outline-primary"
                                       title="Сбросить фильтр">
                                        <i class="fas fa-times text-danger"></i>
                                    </a>
                                </div>
                            @endisset
                        </div>
                    </form>
                </div>
            </div>

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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const barcodeInput = document.querySelector('input[name="storage_barcode"]');
            if (barcodeInput) {
                // Автофокус при загрузке страницы
                barcodeInput.focus();
            }
        });
    </script>
@endpush
