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
                        <a href="{{ route('marketplace_orders.create') }}" class="btn btn-primary mr-3 mb-3">Добавить заказ вручную</a>

                        <a href="{{ route('marketplace_api.newOrder') }}" class="btn btn-success mr-3 mb-3">Загрузить заказы с API</a>

                        <a href="{{ route('marketplace_api.check_cancelled') }}" class="btn btn-warning mr-3 mb-3">Проверить отмененные заказы</a>
                    @endif

                    <a href="{{ route('marketplace_orders.index', ['status' => 0, 'marketplace_id' => request('marketplace_id')]) }}"
                       class="btn btn-link mr-3 mb-3">Новые заказы</a>

                    <a href="{{ route('marketplace_orders.index', ['status' => 3, 'marketplace_id' => request('marketplace_id')]) }}"
                       class="btn btn-link mr-3 mb-3">Выполненные</a>

                    <div class="form-group col-md-2">
                        <select name="marketplace_id"
                                id="marketplace_id"
                                class="form-control"
                                onchange="updatePageWithQueryParam(this)"
                                required>
                            <option value="" selected>---</option>
                            <option value="1" @if(request()->get('marketplace_id') == 1) selected @endif>OZON</option>
                            <option value="2" @if(request()->get('marketplace_id') == 2) selected @endif>WB</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>

        <div class="row only-on-smartphone">
            @foreach ($orders as $order)
                <div class="col-md-4">
                    <div class="card">
                        <div class="position-relative">
                            <div class="ribbon-wrapper ribbon-lg">
                                <div class="ribbon bg-gradient-gray-dark text-lg">
                                    <img style="width: 80px;"
                                         src="{{ asset($order->marketplace_name) }}"
                                         alt="{{ $order->marketplace_name }}">
                                </div>
                            </div>
                            <div class="card-body">
                                <b>{{ $order->order_id }} </b>
                                <span class="mx-1 badge {{ $order->status_color }}"> {{ $order->status_name }}</span>
                                <b>{{ $order->fulfillment_type }}</b> <br>

                                <div class="my-3">
                                    @php
                                        $orderStatus = true;
                                    @endphp

                                    @foreach($order->items as $item)
                                        @php
                                            if ($item->status != 3){
                                                $orderStatus = false;
                                            }
                                        @endphp
                                        <li>
                                            <b>{{ $item->item->title }} {{ $item->item->width / 100 }} . {{ $item->item->height }}</b>
                                            - {{ $item->quantity }} шт.
                                            @if($item->status == 3) <span class="badge badge-success">Выполнено</span> @endif
                                            @if($item->status == 4) <span class="badge badge-warning">В работе</span> @endif
                                        </li>
                                    @endforeach
                                    <div class="mt-2">
                                        <small class="mr-2">
                                            Создан: <b> {{ now()->parse($order->created_at)->format('d/m/Y H:i') }}</b>
                                        </small>
                                        <badge class="badge
                                        @if($order->created_at->addHours(41)->isPast()) badge-hot
                                        @elseif($order->created_at->addHours(21)->isPast()) badge-old
                                        @else badge-new
                                        @endif">
                                            {{ $order->created_at->diffForHumans(['parts' => 2]) }}
                                        </badge>
                                    </div>
                                </div>

                                @if(auth()->user()->role->name == 'admin')
                                <div class="btn-group" role="group">
                                    <a href="{{ route('marketplace_orders.edit', ['marketplace_order' => $order->id]) }}"
                                       class="btn btn-primary mr-3">
                                        <i class="fas fa-edit"></i> Редактировать
                                    </a>

                                    <form method="POST"
                                          action="{{ route('marketplace_orders.destroy', ['marketplace_order' => $order->id]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger mr-3"
                                                onclick="return confirm('Вы уверены что хотите удалить данный заказ из системы?')">
                                            <i class="fas fa-trash"></i> Удалить
                                        </button>
                                    </form>
                                </div>
                                @endif

                                @if($orderStatus && $order->status != 3)
                                <a href="{{ route('marketplace_orders.complete', ['marketplace_order' => $order->id]) }}"
                                   class="btn btn-success mt-2"
                                   onclick="return confirm('Вы уверены что заказ выполнен?')">
                                    <i class="fas fa-check"></i> Выполнено
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <x-pagination-component :collection="$orders" />
        </div>

        <div class="card only-on-desktop">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Номер заказа</th>
                            <th scope="col">Маркетплейс</th>
                            <th scope="col">Тип</th>
                            <th scope="col">Товары</th>
                            <th scope="col">Создан</th>
                            <th scope="col">Выполнен</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td><span class="badge {{ $order->status_color }}"> {{ $order->status_name }}</span></td>
                                <td>{{ $order->order_id }}</td>
                                <td>
                                    <img style="width: 80px;"
                                         src="{{ asset($order->marketplace_name) }}"
                                         alt="{{ $order->marketplace_name }}">
                                </td>
                                <td>{{ $order->fulfillment_type }}</td>
                                <td>
                                    @php
                                        $orderStatus = true;
                                    @endphp
                                    @foreach($order->items as $item)
                                        @php
                                            if ($item->status != 3){
                                                $orderStatus = false;
                                            }
                                        @endphp
                                        <b>{{ $item->item->title }} {{ $item->item->width }}х{{ $item->item->height }}</b> - {{ $item->quantity }} шт.
                                        @if($item->status == 3) <span class="badge badge-success">Выполнено</span> @endif
                                        @if($item->status == 4) <span class="badge badge-warning">В работе</span> @endif
                                        <br>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="mr-2">{{ now()->parse($order->created_at)->format('d/m/Y H:i') }}</span>
                                    <badge class="badge
                                    @if($order->created_at->addHours(41)->isPast()) badge-hot
                                    @elseif($order->created_at->addHours(21)->isPast()) badge-old
                                    @else badge-new
                                    @endif">
                                        {{ $order->created_at->diffForHumans(['parts' => 2]) }}
                                    </badge><br>
                                </td>
                                <td>{{ is_null($order->completed_at) ? '' : now()->parse($order->completed_at)->format('d/m/Y H:i') }}</td>

                                <td style="width: 100px">
                                    @if(auth()->user()->role->name == 'admin')
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('marketplace_orders.edit', ['marketplace_order' => $order->id]) }}" class="btn btn-primary mr-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('marketplace_orders.destroy', ['marketplace_order' => $order->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                    onclick="return confirm('Вы уверены что хотите удалить данный заказ из системы?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    @endif

                                    @if($orderStatus && $order->status != 3)
                                        <a href="{{ route('marketplace_orders.complete', ['marketplace_order' => $order->id]) }}"
                                           class="btn btn-success mt-2"
                                           onclick="return confirm('Вы уверены что заказ выполнен?')">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                <x-pagination-component :collection="$orders" />

            </div>
        </div>
    </div>
@stop

@push('css')
    <link href="{{ asset('css/desktop_or_smartphone_card_style.css') }}" rel="stylesheet"/>
    <link href="{{ asset('css/badges.css') }}" rel="stylesheet"/>
@endpush

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
