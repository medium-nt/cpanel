@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('marketplace_order_items.index', ['status' => 'in_work']) }}" class="btn btn-link mr-3 mb-3">В работе</a>
                <a href="{{ route('marketplace_order_items.index', ['status' => 'new']) }}" class="btn btn-link mr-3 mb-3">Новые заказы</a>
                <a href="{{ route('marketplace_order_items.index', ['status' => 'done']) }}" class="btn btn-link mr-3 mb-3">Выполненные</a>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Название</th>
                            <th scope="col">Ширина</th>
                            <th scope="col">Высота</th>
                            <th scope="col">Кол-во</th>
                            <th scope="col">Маркетплейс</th>
                            <th scope="col">Создан</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="badge {{ $item->status_color }}"> {{ $item->status_name }}</span></td>
                                <td>{{ $item->item->title }}</td>
                                <td>{{ $item->item->width }}</td>
                                <td>{{ $item->item->height }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->marketplaceOrder->marketplace_name }}</td>
                                <td>{{ now()->parse($item->created_at)->format('d/m/Y H:i') }}</td>

                                <td style="width: 100px">
                                    @if(auth()->user()->role->name == 'seamstress')
                                        @switch($item->status)
                                            @case(0)
                                                <div class="btn-group" role="group">
                                                    <form action="{{ route('marketplace_order_items.startWork', ['marketplace_order_item' => $item->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-success mr-1"
                                                                title="Взять работу"
                                                                onclick="return confirm('Вы уверены что хотите взять данный товар в работу?')">
                                                            <i class="fas fa-drafting-compass"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                @break
                                            @case(4)
                                                <div class="btn-group" role="group">
                                                    <form action="{{ route('marketplace_order_items.done', ['marketplace_order_item' => $item->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-success mr-1"
                                                                title="Сдать работу"
                                                                onclick="return confirm('Вы уверены что заказ выполнен?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                @break
                                        @endswitch
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                <x-pagination-component :collection="$items" />

            </div>
        </div>
    </div>
@stop
