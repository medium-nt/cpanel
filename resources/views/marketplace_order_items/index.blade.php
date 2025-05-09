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

                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'new',
                    'seamstress_id' => request('seamstress_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id')
                ]) }}"
                   class="btn btn-link">Новые</a>

                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'done',
                    'seamstress_id' => request('seamstress_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id')
                ]) }}"
                   class="btn btn-link">Выполненные</a>

            </div>
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
                        @php
                            $allCalcWidth = 0;
                        @endphp

                        @foreach ($items as $item)
                            @php
                                $allCalcWidth += $item->item->width * $item->quantity;
                            @endphp
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
                                <td>
                                    <span class="mr-2">{{ now()->parse($item->created_at)->format('d/m/Y H:i') }}</span>
                                    <badge class="badge
                                    @if($item->created_at->addHours(41)->isPast()) badge-hot
                                    @elseif($item->created_at->addHours(21)->isPast()) badge-old
                                    @else badge-new
                                    @endif">
                                        {{ $item->created_at->diffForHumans(['parts' => 2]) }}
                                    </badge><br>
                                </td>
                                <td>{{ is_null($item->completed_at) ? '' : now()->parse($item->completed_at)->format('d/m/Y H:i') }}</td>

                                <td style="width: 100px">
                                    @if(auth()->user()->role->name == 'seamstress' || auth()->user()->role->name == 'admin')
                                        @switch($item->status)
                                            @case(0)
                                                @if(auth()->user()->role->name != 'admin')
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
                                                @endif
                                                @break
                                            @case(4)
                                                <div class="btn-group" role="group">
                                                    @if(auth()->user()->role->name != 'admin')
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
                                                    @endif

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
                        <tfoot>
                            <tr>
                                <td colspan="4"></td>
                                <td colspan="3" style="text-align: center">
                                    ИТОГО: <b>{{ $allCalcWidth / 100 }}</b> м.п.
                                </td>
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
                        ИТОГО: <b>{{ $allCalcWidth / 100 }}</b> м.п.
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
                                <span class="mx-1 badge {{ $item->status_color }}"> {{ $item->status_name }}</span>
                                <b> {{ $item->marketplaceOrder->fulfillment_type }}</b> <br>

                                <div class="my-3">
                                    Товар: <b> {{ $item->item->title }} </b> х <b>{{ $item->quantity }} шт.</b><br>
                                    Размеры: <b> {{ $item->item->width / 100 }}</b> . <b> {{ $item->item->height }}</b><br>
                                    <small>
                                    </small>
                                    <small class="mr-2">
                                        Создан: <b> {{ now()->parse($item->created_at)->format('d/m/Y H:i') }}</b>
                                    </small>
                                    <badge class="badge
                                    @if($item->created_at->addHours(41)->isPast()) badge-hot
                                    @elseif($item->created_at->addHours(21)->isPast()) badge-old
                                    @else badge-new
                                    @endif">
                                        {{ $item->created_at->diffForHumans(['parts' => 2]) }}
                                    </badge>
                                </div>

                                @if(auth()->user()->role->name == 'seamstress' || auth()->user()->role->name == 'admin')
                                    @switch($item->status)
                                        @case(0)
                                            @if(auth()->user()->role->name != 'admin')
                                                <div class="btn-group" role="group">
                                                    <form action="{{ route('marketplace_order_items.startWork', ['marketplace_order_item' => $item->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-success mr-1"
                                                                title="Взять работу"
                                                                onclick="return confirm('Вы уверены что хотите взять данный товар в работу?')">
                                                            <i class="fas fa-drafting-compass"></i> Взять работу
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                            @break
                                        @case(4)
                                            <div class="btn-group" role="group">
                                                @if(auth()->user()->role->name != 'admin')
                                                    <form action="{{ route('marketplace_order_items.done', ['marketplace_order_item' => $item->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-success mr-4"
                                                                title="Сдать работу"
                                                                onclick="return confirm('Вы уверены что заказ выполнен?')">
                                                            <i class="fas fa-check"></i> Сдать работу
                                                        </button>
                                                    </form>
                                                @endif

                                                <form action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                      method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-danger mr-1"
                                                            title="Отменить заказ"
                                                            onclick="return confirm('Вы уверены что хотите отказаться от заказа?')">
                                                        <i class="fas fa-times"></i> Отменить заказ
                                                    </button>
                                                </form>
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
