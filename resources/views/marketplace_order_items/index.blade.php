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
                    @if(auth()->user()->isAdmin() || auth()->user()->isStorekeeper() || auth()->user()->isOtk())
                    <div class="form-group col-md-3">
                        <select name="user_id"
                                id="user_id"
                                class="form-control"
                                onchange="updatePageWithQueryParam(this)"
                                required>
                            <option value="" selected>Все</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                        @if(request('user_id') == $user->id) selected @endif
                                >{{ $user->short_name }}</option>
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

                        <div class="form-group col-md-1">
                            <select name="fulfillment_type"
                                    id="fulfillment_type"
                                    class="form-control"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected>Все типы</option>
                                <option value="FBO"
                                        @if(request()->get('fulfillment_type') == 'FBO') selected @endif>
                                    FBO
                                </option>
                                <option value="FBS"
                                        @if(request()->get('fulfillment_type') == 'FBS') selected @endif>
                                    FBS
                                </option>
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <select name="material"
                                    id="material"
                                    class="form-control"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected>Все материалы</option>
                                @foreach($titleMaterials as $tm)
                                    <option value="{{ $tm->title }}"
                                            @if(request()->get('material') == $tm->title) selected @endif
                                    >{{ $tm->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <select name="width"
                                    id="width"
                                    class="form-control"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected>Все ширины</option>
                                @foreach($widthMaterials as $wm)
                                    <option value="{{ $wm->width }}"
                                            @if(request()->get('width') == $wm->width) selected @endif
                                    >{{ $wm->width }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <select name="height"
                                    id="height"
                                    class="form-control"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected>Все высоты</option>
                                @foreach($heightMaterials as $hm)
                                    <option value="{{ $hm->height }}"
                                            @if(request()->get('height') == $hm->height) selected @endif
                                    >{{ $hm->height }}</option>
                                @endforeach
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

                @if(auth()->user()->isAdmin())
                    <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'new',
                    'user_id' => request('user_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id'),
                    'fulfillment_type' => request('fulfillment_type'),
                    'material' => request('material'),
                    'width' => request('width'),
                    'height' => request('height')
                ]) }}"
                       class="btn btn-link">Новые</a>
                @endif

                @if(auth()->user()->isCutter() || auth()->user()->isAdmin())
                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'cutting',
                    'user_id' => request('user_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id'),
                    'fulfillment_type' => request('fulfillment_type'),
                    'material' => request('material'),
                    'width' => request('width'),
                    'height' => request('height')
                ]) }}"
                   class="btn btn-link">На раскрое</a>
                @endif

                @if(auth()->user()->isCutter() || auth()->user()->isAdmin() || auth()->user()->isOtk())
                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'cut',
                    'user_id' => request('user_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id'),
                    'fulfillment_type' => request('fulfillment_type'),
                    'material' => request('material'),
                    'width' => request('width'),
                    'height' => request('height')
                ]) }}"
                   class="btn btn-link">Раскроено</a>
                @endif

                @if(!auth()->user()->isOtk())
                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'in_work',
                    'user_id' => request('user_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id'),
                    'fulfillment_type' => request('fulfillment_type'),
                    'material' => request('material'),
                    'width' => request('width'),
                    'height' => request('height')
                ]) }}"
                   class="btn btn-link">В работе</a>
                @endif

                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'labeling',
                    'user_id' => request('user_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id'),
                    'fulfillment_type' => request('fulfillment_type'),
                    'material' => request('material'),
                    'width' => request('width'),
                    'height' => request('height')
                ]) }}"
                   class="btn btn-link">Стикеровка</a>

                @if(!auth()->user()->isOtk())
                <a href="{{ route('marketplace_order_items.index', [
                    'status' => 'done',
                    'user_id' => request('user_id'),
                    'date_start' => request('date_start'),
                    'date_end' => request('date_end'),
                    'marketplace_id' => request('marketplace_id'),
                    'fulfillment_type' => request('fulfillment_type'),
                    'material' => request('material'),
                    'width' => request('width'),
                    'height' => request('height')
                ]) }}"
                   class="btn btn-link">Готовые</a>
                @endif
            </div>
        </div>

        @if(auth()->user()->isAdmin() || auth()->user()->isStorekeeper())
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <input type="text"
                               name="search"
                               id="search"
                               class="form-control"
                               placeholder="Поиск по номеру заказа или ШК"
                               value="{{ request('search') }}"
                               onchange="updatePageWithQueryParam(this)">
                    </div>
                </div>
            </div>
        </div>
        @endif

        @php
            $fewMaterials = [];
//            foreach($materials as $material) {
//                if($material['quantity'] <= 100) {
//                    $fewMaterials[] = $material;
//                }
//            }
        @endphp

        @if(count($fewMaterials) != 0)
            <div class="alert alert-danger" role="alert">
                <h5 class="alert-heading">
                    Внимание! Некоторые заказы могут быть не доступны к пошиву.
                    В цехе осталось мало материалов:
                </h5>
                @foreach($fewMaterials as $material)
                    <li>
                        {{ $material['material']->title }} - {{ $material['quantity'] }} {{ $material['material']->unit }}
                    </li>
                @endforeach
            </div>
        @endif

        <div class="card only-on-desktop">
            <div class="card-body">

                @if(auth()->user()->isSeamstress() || auth()->user()->isCutter())
                <a href="{{ route('marketplace_order_items.getNewOrderItem') }}"
                   class="btn btn-primary mb-3 getNewOrderItem">Получить новый
                    заказ</a>
                    @if(auth()->user()->is_cutter || auth()->user()->isCutter())
                        <a href="{{ route('marketplace_order_items.printCutting') }}"
                           target="_blank"
                           class="btn btn-outline-secondary ml-3 mb-3"><i
                                class="fas fa-print mr-1"></i>Распечатать заказы</a>
                    @endif
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th style="text-align: center" scope="col">#</th>
                            <th style="text-align: center" scope="col">Статус
                            </th>
                            <th style="text-align: center" scope="col">Номер
                                заказа
                            </th>
                            <th style="text-align: center" scope="col">
                                Название
                            </th>
                            <th style="text-align: center" scope="col">Ширина
                            </th>
                            <th style="text-align: center" scope="col">Высота
                            </th>
                            <th style="text-align: center" scope="col">
                                Маркетплейс
                            </th>
                            <th style="text-align: center" scope="col">Тип</th>
                            @if(auth()->user()->isAdmin() || auth()->user()->isStorekeeper() || auth()->user()->isOtk())
                                <th style="text-align: center" scope="col">
                                    Сотрудники
                                </th>
                            @endif
                            <th style="text-align: center" scope="col">Создан
                            </th>
                            <th style="text-align: center" scope="col">
                                Выполнен
                            </th>
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
                                <td style="text-align: center"><span
                                        class="badge {{ $item->status_color }}"> {{ $item->status_name }}</span>
                                </td>
                                <td style="text-align: center">
                                    {{ $item->marketplaceOrder->order_id }}
                                    @if($item->status == 3 && (auth()->user()->isAdmin() || auth()->user()->isStorekeeper()))
                                        @php
                                            $routeName = $item->marketplaceOrder->fulfillment_type === 'FBO'
                                                ? 'marketplace_api.fbo_barcode'
                                                : 'marketplace_api.barcode';
                                        @endphp
                                        <a href="{{ route($routeName, ['marketplaceOrderId' => $item->marketplaceOrder->order_id]) }}"
                                           class="btn btn-outline-secondary btn-sm ml-1"
                                           style="padding: 1px 5px;"
                                           target="_blank">
                                            <i class="fas fa-barcode"></i>
                                        </a>
                                    @endif
                                </td>
                                <td style="text-align: center">{{ $item->item->title }}</td>
                                <td style="text-align: center">{{ $item->item->width }}</td>
                                <td style="text-align: center">{{ $item->item->height }}</td>
                                <td style="text-align: center">
                                    <img style="width: 80px;"
                                         src="{{ asset($item->marketplaceOrder->marketplace_name) }}"
                                         alt="{{ $item->marketplaceOrder->marketplace_name }}">
                                </td>
                                <td style="text-align: center">{{ $item->marketplaceOrder->fulfillment_type }}</td>

                                @if(auth()->user()->isAdmin() || auth()->user()->isStorekeeper() || auth()->user()->isOtk())
                                    <td style="font-size: 12px;">
                                        @if($item->cutter_id)
                                            <b>Закройщик:</b> {{ $item->cutter?->shortName }}
                                            <br>
                                        @endif
                                        @if($item->seamstress_id)
                                            <b>Швея:</b> {{ $item->seamstress?->shortName }}
                                        @endif
                                    </td>
                                @endif

                                <td style="text-align: center">
                                    <span
                                        class="mr-2">{{ now()->parse($item->created_at)->format('d/m/Y H:i') }}</span>
                                    <badge class="badge
                                        @if($item->created_at->addHours(41)->isPast()) badge-hot
                                        @elseif($item->created_at->addHours(21)->isPast()) badge-old
                                        @else badge-new
                                        @endif">
                                        {{ $item->created_at->diffForHumans(['parts' => 2]) }}
                                    </badge>
                                    <br>
                                </td>
                                <td style="text-align: center">{{ is_null($item->completed_at) ? '' : now()->parse($item->completed_at)->format('d/m/Y H:i') }}</td>

                                <td style="width: 100px">
                                    @if(auth()->user()->isSeamstress() || auth()->user()->isCutter() || auth()->user()->isAdmin())
                                        <div class="btn-group" role="group">
                                            @switch($item->status)
                                                @case(4)
                                                    @if(auth()->user()->isSeamstress())
                                                        <form
                                                            action="{{ route('marketplace_order_items.labeling', ['marketplace_order_item' => $item->id]) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <button
                                                                type="submit"
                                                                class="btn btn-success mr-3"
                                                                title="На стикеровку"
                                                                onclick="return confirm('Вы уверены что заказ выполнен?')">
                                                                <i class="far fa-sticky-note"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <form
                                                        action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button
                                                            type="submit"
                                                            title="Отменить заказ"
                                                            class="btn btn-danger mr-3"
                                                            @if(auth()->user()->isAdmin())
                                                                onclick="return confirm('Вы уверены что хотите снять товар со швеи?')"
                                                            @elseif(auth()->user()->isSeamstress())
                                                                onclick="return confirm('Вы уверены что хотите отказаться от заказа? Вам будет начислен штраф')"
                                                            @endif
                                                        >
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>

                                                    @if($bonus > 0 && auth()->user()->isSeamstress())
                                                        <span
                                                            class="badge border border-warning text-dark p-2"
                                                            style="font-size: 20px;">
                                                                <b>+ {{ $bonus * $item->item->width / 100 }}</b>
                                                            <i class="fas fa-star text-warning"></i>
                                                        </span>
                                                    @endif
                                                    @break
                                                @case(5)
                                                    @if(auth()->user()->isAdmin())
                                                        <form
                                                            action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <button
                                                                type="submit"
                                                                class="btn btn-danger mr-1"
                                                                title="Отменить заказ"
                                                                onclick="return confirm('Вы уверены что хотите снять товар со швеи?')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                        @break
                                                @case(7)
                                                    @if(auth()->user()->isCutter())
                                                        <form
                                                            action="{{ route('marketplace_order_items.completeCutting', ['marketplace_order_item' => $item->id]) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <button
                                                                type="submit"
                                                                class="btn btn-success mr-3"
                                                                title="Сдать откроенное"
                                                                onclick="return confirm('Вы уверены что заказ выполнен?')">
                                                                <i class="far fa-sticky-note"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <form
                                                        action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit"
                                                                class="btn btn-danger mr-3"
                                                                title="Отменить заказ"
                                                                @if(auth()->user()->isAdmin())
                                                                    onclick="return confirm('Вы уверены что хотите снять товар с закроя?')"
                                                                @elseif(auth()->user()->isCutter())
                                                                    onclick="return confirm('Вы уверены что хотите отказаться от заказа? Вам будет начислен штраф')"
                                                            @endif
                                                        >
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>

                                                    @if($bonus > 0 && auth()->user()->isCutter())
                                                        <span
                                                            class="badge border border-warning text-dark p-2"
                                                            style="font-size: 20px;">
                                                            <b>+ {{ $bonus * $item->item->width / 100 }}</b>
                                                            <i class="fas fa-star text-warning"></i>
                                                        </span>
                                                    @endif

                                                    @break
                                            @endswitch
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="4" style="text-align: right">
                                Итого на странице <b>{{ $allCount }}</b>
                                шт.:
                            </td>
                            <td style="text-align: center">
                                <b>{{ $allCalcWidth / 100 }}</b> п.м.
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                {{-- Pagination --}}
                <x-pagination-component :collection="$items"/>

            </div>
        </div>

        <div class="row only-on-smartphone">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        Итого на странице: <b>{{ $allCalcWidth / 100 }}</b> п.м.
                        (<b>{{ $allCount }}</b> шт.)
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        @if(auth()->user()->isSeamstress() || auth()->user()->isCutter())
                            <a href="{{ route('marketplace_order_items.getNewOrderItem') }}"
                               class="btn btn-primary getNewOrderItem">Получить
                                новый заказ</a>
                        @endif

                            @if(auth()->user()->is_cutter || auth()->user()->isCutter())
                            <a href="{{ route('marketplace_order_items.printCutting') }}"
                               target="_blank"
                               class="btn btn-outline-secondary ml-3"><i
                                    class="fas fa-print"></i></a>
                        @endif
                    </div>
                </div>
            </div>
            @foreach ($items as $item)
                <div class="col-md-4">
                    <div class="card">
                        <div class="position-relative">
                            <div class="ribbon-wrapper ribbon-lg">
                                <div
                                    class="ribbon bg-gradient-gray-dark text-lg">
                                    <img style="width: 80px;"
                                         src="{{ asset($item->marketplaceOrder->marketplace_name) }}"
                                         alt="{{ $item->marketplaceOrder->marketplace_name }}">
                                </div>
                            </div>
                            <div class="card-body">
                                <b>{{ $item->marketplaceOrder->order_id }} </b>
                                <b> {{ $item->marketplaceOrder->fulfillment_type }}</b><br>
                                <span
                                    class="badge {{ $item->status_color }}"> {{ $item->status_name }}</span>
                                <br>
                                @isset($item->cutter_id)
                                    <small>Кроил:
                                        <b>{{ $item->cutter->shortName ?? '' }}</b>
                                    </small>
                                    <br>
                                @endisset
                                @isset($item->seamstress)
                                    <small>Швея:
                                        <b>{{ $item->seamstress->shortName ?? '' }}</b>
                                    </small>
                                @endisset
                                <div class="my-2">
                                    Товар: <span
                                        style="font-size: 25px;"> <b> {{ $item->item->title }} </b> х <b>{{ $item->quantity }} шт.</b></span><br>
                                    Ширина: <b><span
                                            style="font-size: 25px;"> {{ $item->item->width }} </span>
                                    </b> см.<br>
                                    Высота: <b><span
                                            style="font-size: 25px;"> {{ $item->item->height }} </span>
                                    </b> см.<br>
                                    <small class="mr-2">
                                        Создан:
                                        <b> {{ now()->parse($item->created_at)->format('d/m/Y H:i') }}</b>
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

                                @if(auth()->user()->isSeamstress() || auth()->user()->isCutter() || auth()->user()->isAdmin())
                                    <div class="btn-group" role="group">
                                    @switch($item->status)
                                        @case(4)
                                                @if(auth()->user()->isSeamstress())
                                                    <form
                                                        action="{{ route('marketplace_order_items.labeling', ['marketplace_order_item' => $item->id]) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit"
                                                                class="btn btn-success mr-3"
                                                                title="На стикеровку"
                                                                onclick="return confirm('Вы уверены что заказ выполнен?')">
                                                            <i class="far fa-sticky-note"></i>
                                                            На стикеровку
                                                        </button>
                                                    </form>
                                                @endif

                                                <form
                                                    action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit"
                                                            class="btn btn-danger mr-3"
                                                            title="Отменить заказ"
                                                            @if(auth()->user()->isAdmin())
                                                                onclick="return confirm('Вы уверены что хотите снять товар со швеи?')"
                                                            @elseif(auth()->user()->isSeamstress())
                                                                onclick="return confirm('Вы уверены что хотите отказаться от заказа? Вам будет начислен штраф')"
                                                        @endif
                                                    >
                                                        <i class="fas fa-times"></i>

                                                    </button>
                                                </form>

                                                @if($bonus > 0 && auth()->user()->isSeamstress())
                                                    <span
                                                        class="badge border border-warning text-dark p-2"
                                                        style="font-size: 20px;">
                                                        <b>+ {{ $bonus * $item->item->width / 100 }}</b> <i
                                                            class="fas fa-star text-warning"></i>
                                                    </span>
                                                @endif

                                            @break
                                        @case(5)
                                                @if(auth()->user()->isAdmin())
                                                    <form
                                                        action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit"
                                                                class="btn btn-danger mr-1"
                                                                title="Отменить заказ"
                                                                onclick="return confirm('Вы уверены что хотите снять товар со швеи?')">
                                                            <i class="fas fa-times"></i>
                                                            Отменить заказ
                                                        </button>
                                                    </form>
                                                @endif
                                            @break
                                        @case(7)
                                            @if(auth()->user()->isCutter())
                                                <form
                                                    action="{{ route('marketplace_order_items.completeCutting', ['marketplace_order_item' => $item->id]) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit"
                                                            class="btn btn-success mr-3"
                                                            title="Сдать откроенное"
                                                            onclick="return confirm('Вы уверены что заказ выполнен?')">
                                                        <i class="far fa-sticky-note"></i>
                                                        Сдать работу
                                                    </button>
                                                </form>
                                            @endif

                                                <form
                                                    action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit"
                                                            class="btn btn-danger mr-3"
                                                            title="Отменить заказ"
                                                            @if(auth()->user()->isAdmin())
                                                                onclick="return confirm('Вы уверены что хотите снять товар с закроя?')"
                                                            @elseif(auth()->user()->isCutter())
                                                                onclick="return confirm('Вы уверены что хотите отказаться от заказа? Вам будет начислен штраф')"
                                                        @endif
                                                    >
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>

                                                @if($bonus > 0 && auth()->user()->isCutter())
                                                    <span
                                                        class="badge border border-warning text-dark p-2"
                                                        style="font-size: 20px;">
                                                                <b>+ {{ $bonus * $item->item->width / 100 }}</b> <i
                                                            class="fas fa-star text-warning"></i>
                                                            </span>
                                            @endif

                                            @break
                                        @case(3)
                                                {{--<div class="btn-group" role="group">--}}
                                                {{--    @if(auth()->user()->isAdmin())--}}
                                                {{--        <form action="{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}"--}}
                                                {{--              method="POST">--}}
                                                {{--            @csrf--}}
                                                {{--            @method('PUT')--}}
                                                {{--            <button type="submit" class="btn btn-danger mr-1"--}}
                                                {{--                    title="Отменить заказ"--}}
                                                {{--                    onclick="return confirm('Вы уверены что хотите отменить уже выполненный заказа?')">--}}
                                                {{--                <i class="fas fa-times"></i> Отменить заказ--}}
                                                {{--            </button>--}}
                                                {{--        </form>--}}
                                                {{--    @endif--}}
                                                {{--</div>--}}
                                            @break
                                    @endswitch
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <x-pagination-component :collection="$items"/>
        </div>
    </div>
@stop

@push('css')
    <link href="{{ asset('css/desktop_or_smartphone_card_style.css') }}"
          rel="stylesheet"/>
    <link href="{{ asset('css/badges.css') }}" rel="stylesheet"/>
@endpush

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>

    <script>
        $('.getNewOrderItem').on('click', function () {
            var button = $(this);
            button.hide();
            button.after('<span class="saving"><i class="fas fa-spinner fa-2x fa-pulse mr-1"></i>Выбираем заказ...</span>');

            setTimeout(function () {
                button.next('.saving').remove();
                button.after('<span class="saving"><i class="fas fa-spinner fa-2x fa-pulse mr-1"></i>Получаем список заказов...</span>');
            }, 3000);

            setTimeout(function () {
                button.next('.saving').remove();
                button.after('<span class="saving"><i class="fas fa-spinner fa-2x fa-pulse mr-1"></i>Выбираем подходящий...</span>');
            }, 6000);

            setTimeout(function () {
                button.next('.saving').remove();
                button.after('<span class="saving"><i class="fas fa-spinner fa-2x fa-pulse mr-1"></i>Еще немного...</span>');
            }, 10000);
        });
    </script>
@endpush
