@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('supply_boxes.index', ['marketplace_supply' => $supply]) }}"
                   class="btn btn-link mb-3">
                    &larr; Назад к коробам
                </a>

                <h4>{{ $box->number }}</h4>
            </div>
        </div>

        @if($box->orders->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Заказы в коробе
                        ({{ $box->orders->count() }})</h3>
                </div>
                <div class="card-body">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th>Заказ</th>
                            <th>Товар</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($box->orders as $order)
                            @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $order->order_id }}</td>
                                    <td>{{ $item->item?->title ?? '-' }} {{ $item->item?->width }}
                                        x{{ $item->item?->height }}</td>
                                    @if($loop->first)
                                        <td rowspan="{{ $order->items->count() }}">
                                            <form
                                                action="{{ route('supply_boxes.remove_order', ['marketplace_supply' => $supply, 'box' => $box, 'order' => $order]) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Убрать заказ из короба?')">
                                                    Удалить
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <p class="text-muted mt-3">Короб пуст.</p>
        @endif
    </div>
@stop
