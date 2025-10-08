@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <form action="" method="get">
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <a href="{{ route('warehouse_of_item.to_pick_list') }}"
                                   class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-arrow-left mr-2"></i> Назад
                                </a>
                            </div>
                            <div class="col-md-8 mb-3">
                                <input type="text" name="barcode"
                                       class="form-control"
                                       placeholder="Поиск"
                                       autofocus
                                       value="{{ request('barcode') }}">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100">Найти
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @isset($barcode)
        @if($item != null)
            <div class="card">
                <div class="card-body">
                    <p>Товар <b>{{ $itemName }}</b></p>

                    @if($order->isStickering())
                        <a href="{{ route('marketplace_api.barcode', ['marketplaceOrderId' => $item->marketplaceOrder->order_id]) }}"
                           class="btn btn-outline-secondary btn-lg mr-3 mb-3"
                           target="_blank">
                            <i class="fas fa-barcode mr-2"></i> Печать стикера
                            маркетплейса
                        </a>

                        <a href="{{ route('warehouse_of_item.done', ['marketplace_order' => $order->id]) }}"
                           class="btn btn-success btn-lg mr-4 mb-3"
                           title="На стикеровку"
                           onclick="return confirm('Вы уверены что выбираете этот товар?')">
                            <i class="far fa-sticky-note mr-2"></i> Отправить на
                            поставку
                        </a>
                    @else
                        <form
                            action="{{ route('warehouse_of_item.labeling', ['marketplace_order' => $order->id, 'marketplace_order_item' => $item->id]) }}"
                            method="POST">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-success mr-4"
                                    title="На стикеровку"
                                    onclick="return confirm('Вы уверены что выбираете этот товар?')">
                                <i class="far fa-sticky-note mr-2"></i>
                                Отправить на стикеровку
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @else
            <div class="alert alert-danger" role="alert">
                Товар <b>{{ $itemName }}</b> по данному штрих-коду не найден.
            </div>
        @endif
    @endisset

    @if(!$order->isStickering())
        <div class="card">
            <div class="card-body">
                <a href="{{ route('warehouse_of_item.to_work', ['marketplace_order' => $order->id]) }}"
                   class="btn btn-danger"
                   onclick="return confirm('Вы уверены?')">
                    Такого товара нет на складе
                </a>
                <br><small>(отправить на отшив в цех)</small>
                <br><br>
                Числится на складе: <b id="count">{{ $count }}</b>
            </div>
        </div>
    @endif
@stop
