@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <div class="row">
                    @if(auth()->user()->role->name == 'admin')
                    <div class="form-group col-md-3">
                        <select name="seamstress_id"
                                id="seamstress_id"
                                class="form-control"
                                onchange="updatePageWithQueryParam(this)"
                                required>
                            <option value="" selected>Все</option>
                            @foreach($seamstresses as $seamstress)
                                <option value="{{ $seamstress->id }}"
                                        @if(request('seamstress_id') == $seamstress->id) selected @endif
                                >{{ $seamstress->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="form-group col-md-3">
                        <input type="date"
                               name="date_start"
                               id="date_start"
                               class="form-control"
                               onchange="updatePageWithQueryParam(this)"
                               value="{{ request('date_start') }}">
                    </div>

                    <div class="form-group col-md-3">
                        <input type="date"
                               name="date_end"
                               id="date_end"
                               class="form-control"
                               onchange="updatePageWithQueryParam(this)"
                               value="{{ request('date_end') }}">
                    </div>

                </div>

                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'in_work',
                    'seamstress_id' => request('seamstress_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end')
                ]) }}"
                   class="btn btn-link mr-3 mb-3">В работе</a>

                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'new',
                    'seamstress_id' => request('seamstress_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end')
                ]) }}"
                   class="btn btn-link mr-3 mb-3">Новые заказы</a>

                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'done',
                    'seamstress_id' => request('seamstress_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end')
                ]) }}"
                   class="btn btn-link mr-3 mb-3">Выполненные</a>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Номер заказа</th>
                            <th scope="col">Название</th>
                            <th scope="col">Ширина</th>
                            <th scope="col">Высота</th>
                            <th scope="col">Кол-во</th>
                            <th scope="col">Маркетплейс</th>
                            <th scope="col">Тип</th>
                            <th scope="col">Создан</th>
                            <th scope="col">Выполнен</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td><span class="badge {{ $item->status_color }}"> {{ $item->status_name }}</span></td>
                                <td>{{ $item->marketplaceOrder->order_id }}</td>
                                <td>{{ $item->item->title }}</td>
                                <td>{{ $item->item->width }}</td>
                                <td>{{ $item->item->height }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>
                                    <img style="width: 80px;"
                                         src="{{ asset($item->marketplaceOrder->marketplace_name) }}"
                                         alt="{{ $item->marketplaceOrder->marketplace_name }}">
                                </td>
                                <td>{{ $item->marketplaceOrder->fulfillment_type }}</td>
                                <td>{{ now()->parse($item->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ is_null($item->completed_at) ? '' : now()->parse($item->completed_at)->format('d/m/Y H:i') }}</td>

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

                                                    <form action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-danger mr-1"
                                                                title="Отменить заказ"
                                                                onclick="return confirm('Вы уверены что хотите отказаться от заказа?')">
                                                            <i class="fas fa-times"></i>
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

@push('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@endpush

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
