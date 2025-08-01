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
                    @if(auth()->user()->role->name == 'admin' || auth()->user()->role->name == 'storekeeper')
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
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id')
                ]) }}"
                   class="btn btn-link">В работе</a>

                @if(auth()->user()->role->name === 'admin')
                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'new',
                    'seamstress_id' => request('seamstress_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id')
                ]) }}"
                   class="btn btn-link">Новые</a>
                @endif

                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'labeling',
                    'seamstress_id' => request('seamstress_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id')
                ]) }}"
                   class="btn btn-link">Стикеровка</a>

                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'done',
                    'seamstress_id' => request('seamstress_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id')
                ]) }}"
                   class="btn btn-link">Готовые</a>

            </div>
        </div>

        <div class="card only-on-desktop">
            <div class="card-body">

                @if(auth()->user()->role->name == 'seamstress')
                <a href="{{ route('marketplace_order_items.getNewOrderItem') }}"
                   class="btn btn-primary mb-3">Получить новый заказ</a>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th style="text-align: center" scope="col">#</th>
                            <th style="text-align: center" scope="col">Статус</th>
                            <th style="text-align: center" scope="col">Номер заказа</th>
                            <th style="text-align: center" scope="col">Название</th>
                            <th style="text-align: center" scope="col">Ширина</th>
                            <th style="text-align: center" scope="col">Высота</th>
                            <th style="text-align: center" scope="col">Кол-во</th>
                            <th style="text-align: center" scope="col">Маркетплейс</th>
                            <th style="text-align: center" scope="col">Тип</th>
                            <th style="text-align: center" scope="col">Создан</th>
                            <th style="text-align: center" scope="col">Выполнен</th>
                            <th style="text-align: center" scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $allCalcWidth = 0;
                            $allCount = 0;
                        @endphp

                        @foreach ($items as $item)
                            @php
                                $allCalcWidth += $item->item->width * $item->quantity;
                                $allCount += $item->quantity;
                            @endphp
                            <tr>
                                <td style="text-align: center">{{ $item->id }}</td>
                                <td style="text-align: center"><span class="badge {{ $item->status_color }}"> {{ $item->status_name }}</span></td>
                                <td style="text-align: center">
                                    {{ $item->marketplaceOrder->order_id }}
                                    @if($item->status == 3 && auth()->user()->role_id == 3)
                                    <a href="{{ route('marketplace_api.barcode', ['marketplaceOrderId' => $item->marketplaceOrder->order_id]) }}"
                                       class="btn btn-outline-secondary btn-sm ml-1"
                                       style="padding: 0px 5px;"
                                       target="_blank">
                                        <i class="fas fa-barcode"></i>
                                    </a>
                                    @endif
                                </td>
                                <td style="text-align: center">{{ $item->item->title }}</td>
                                <td style="text-align: center">{{ $item->item->width }}</td>
                                <td style="text-align: center">{{ $item->item->height }}</td>
                                <td style="text-align: center">{{ $item->quantity }}</td>
                                <td style="text-align: center">
                                    <img style="width: 80px;"
                                         src="{{ asset($item->marketplaceOrder->marketplace_name) }}"
                                         alt="{{ $item->marketplaceOrder->marketplace_name }}">
                                </td>
                                <td style="text-align: center">{{ $item->marketplaceOrder->fulfillment_type }}</td>
                                <td style="text-align: center">
                                    <span class="mr-2">{{ now()->parse($item->created_at)->format('d/m/Y H:i') }}</span>
                                    <badge class="badge
                                    @if($item->created_at->addHours(41)->isPast()) badge-hot
                                    @elseif($item->created_at->addHours(21)->isPast()) badge-old
                                    @else badge-new
                                    @endif">
                                        {{ $item->created_at->diffForHumans(['parts' => 2]) }}
                                    </badge><br>
                                </td>
                                <td style="text-align: center">{{ is_null($item->completed_at) ? '' : now()->parse($item->completed_at)->format('d/m/Y H:i') }}</td>

                                <td style="width: 100px">
                                    @if(auth()->user()->role->name == 'seamstress' || auth()->user()->role->name == 'admin')
                                        @switch($item->status)
                                            @case(4)
                                                <div class="btn-group" role="group">
                                                    @if(auth()->user()->role->name != 'admin')
                                                    <form action="{{ route('marketplace_order_items.labeling', ['marketplace_order_item' => $item->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-success mr-1"
                                                                title="На стикеровку"
                                                                onclick="return confirm('Вы уверены что заказ выполнен?')">
                                                            <i class="far fa-sticky-note"></i>
                                                        </button>
                                                    </form>
                                                    @endif

                                                    @if(auth()->user()->role->name == 'admin')
                                                    <form action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-danger mr-1"
                                                                title="Отменить заказ"
                                                                onclick="return confirm('Вы уверены что хотите снять товар со швеи?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                    @endif
                                                </div>
                                                @break
                                            @case(5)
                                                <div class="btn-group" role="group">
                                                    @if(auth()->user()->role->name == 'admin')
                                                        <form action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                              method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="btn btn-danger mr-1"
                                                                    title="Отменить заказ"
                                                                    onclick="return confirm('Вы уверены что хотите снять товар со швеи?')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                                @break
                                            @case(3)
                                                <div class="btn-group" role="group">
                                                    @if(auth()->user()->role->name == 'admin')
                                                        <form action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                              method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="btn btn-danger mr-1"
                                                                    title="Отменить заказ"
                                                                    onclick="return confirm('Вы уверены что хотите отменить уже выполненный заказ?')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                                @break
                                        @endswitch
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align: right">
                                    Итого на странице:
                                </td>
                                <td style="text-align: center"><b>{{ $allCalcWidth / 100 }}</b> п.м.</td>
                                <td></td>
                                <td style="text-align: center"><b>{{ $allCount }}</b> шт.</td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                {{-- Pagination --}}
                <x-pagination-component :collection="$items" />

            </div>
        </div>

        <div class="row only-on-smartphone">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        Итого на странице: <b>{{ $allCalcWidth / 100 }}</b> п.м. (<b>{{ $allCount }}</b> шт.)
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        @if(auth()->user()->role->name == 'seamstress')
                            <a href="{{ route('marketplace_order_items.getNewOrderItem') }}"
                               class="btn btn-primary">Получить новый заказ</a>
                        @endif
                    </div>
                </div>
            </div>
            @foreach ($items as $item)
                <div class="col-md-4">
                    <div class="card">
                        <div class="position-relative">
                            <div class="ribbon-wrapper ribbon-lg">
                                <div class="ribbon bg-gradient-gray-dark text-lg">
                                    <img style="width: 80px;"
                                         src="{{ asset($item->marketplaceOrder->marketplace_name) }}"
                                         alt="{{ $item->marketplaceOrder->marketplace_name }}">
                                </div>
                            </div>
                            <div class="card-body">
                                <b>{{ $item->marketplaceOrder->order_id }} </b>
                                <b> {{ $item->marketplaceOrder->fulfillment_type }}</b><br>
                                <span class="badge {{ $item->status_color }}"> {{ $item->status_name }}</span>

                                <div class="my-3">
                                    Товар: <b> {{ $item->item->title }} </b> х <b>{{ $item->quantity }} шт.</b><br>
                                    Размеры: <b> {{ $item->item->width / 100 }}</b> . <b> {{ $item->item->height }}</b><br>
                                    <small class="mr-2">
                                        Создан: <b> {{ now()->parse($item->created_at)->format('d/m/Y H:i') }}</b>
                                    </small>
                                    <badge class="badge
                                        @if($item->created_at->addHours(41)->isPast()) badge-hot
                                        @elseif($item->created_at->addHours(21)->isPast()) badge-old
                                        @else badge-new
                                        @endif
                                    ">
                                        {{ $item->created_at->diffForHumans(['parts' => 2]) }}
                                    </badge>
                                </div>

                                @if(auth()->user()->role->name == 'seamstress' || auth()->user()->role->name == 'admin')
                                    @switch($item->status)
                                        @case(4)
                                            <div class="btn-group" role="group">
                                                @if(auth()->user()->role->name != 'admin')
                                                    <form action="{{ route('marketplace_order_items.labeling', ['marketplace_order_item' => $item->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-success mr-4"
                                                                title="На стикеровку"
                                                                onclick="return confirm('Вы уверены что заказ выполнен?')">
                                                            <i class="far fa-sticky-note"></i> На стикеровку
                                                        </button>
                                                    </form>
                                                @endif

                                                @if(auth()->user()->role->name == 'admin')
                                                <form action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                      method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-danger mr-1"
                                                            title="Отменить заказ"
                                                            onclick="return confirm('Вы уверены что хотите снять товар со швеи?')">
                                                        <i class="fas fa-times"></i> Отменить заказ
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                            @break
                                        @case(5)
                                            <div class="btn-group" role="group">
                                                @if(auth()->user()->role->name == 'admin')
                                                    <form action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-danger mr-1"
                                                                title="Отменить заказ"
                                                                onclick="return confirm('Вы уверены что хотите снять товар со швеи?')">
                                                            <i class="fas fa-times"></i> Отменить заказ
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                            @break
                                        @case(3)
                                            <div class="btn-group" role="group">
                                                @if(auth()->user()->role->name == 'admin')
                                                    <form action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-danger mr-1"
                                                                title="Отменить заказ"
                                                                onclick="return confirm('Вы уверены что хотите отменить уже выполненный заказа?')">
                                                            <i class="fas fa-times"></i> Отменить заказ
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                            @break
                                    @endswitch
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <x-pagination-component :collection="$items" />
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
