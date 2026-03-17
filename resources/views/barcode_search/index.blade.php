@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    {{-- Карточка с поиском --}}
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form action="" method="get">
                    <div class="form-group">
                        <label for="barcode">Штрихкод</label>
                        <input type="text"
                               id="barcode"
                               name="barcode"
                               class="form-control"
                               placeholder="Введите штрихкод..."
                               autofocus
                               value="{{ request('barcode') }}">
                    </div>
                </form>
                @if($message)
                    <div class="alert alert-danger">
                        <h4>{{ $message }}</h4>
                    </div>
                    {{--                    <p class="text-muted">{{ $message }}</p>--}}
                @endif
            </div>
        </div>
    </div>

    {{-- Карточка с заказом --}}
    @if($order)
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Заказ</h3>
                </div>
                <div class="card-body">
                    <p>№ заказа: <b>{{ $order }}</b></p>
                    {{--                    <p><b>№ заказа:</b> {{ $order->order_id }}</p>--}}
                    {{--                    <p><b>Маркетплейс:</b> {{ $order->marketplace_id == 1 ? 'OZON' : 'WB' }}</p>--}}
                    {{--                    <p><b>Тип:</b> {{ $order->fulfillment_type }}</p>--}}
                </div>
            </div>
        </div>
    @else
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Заказ</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Данные заказа появятся здесь после
                        поиска</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Карточка с товаром --}}
    @if($item)
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Товар</h3>
                </div>
                <div class="card-body">
                    <p><b>Материал:</b> {{ $item->item->title ?? '-' }}</p>
                    <p><b>Длина-ширина:</b> {{ $item->item->width ?? '-' }}
                        x {{ $item->item->height ?? '-' }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Товар</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Данные товара появятся здесь после
                        поиска</p>
                </div>
            </div>
        </div>
    @endif
@stop
