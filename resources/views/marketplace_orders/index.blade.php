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
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('marketplace_orders.create') }}" class="btn btn-primary mr-3 mb-3">Добавить заказ вручную</a>

                        <a href="{{ route('marketplace_api.newOrder') }}" class="btn btn-success mr-3 mb-3">Загрузить заказы с API</a>

                        <a href="{{ route('marketplace_api.check_cancelled') }}" class="btn btn-warning mr-3 mb-3">Проверить отмененные заказы</a>
                    @endif

                        <a href="{{ route('marketplace_orders.index', ['status' => 0, 'marketplace_id' => request('marketplace_id'), 'fulfillment_type' => request('fulfillment_type'), 'cluster' => request('cluster')]) }}"
                       class="btn btn-link mr-3 mb-3">Новые заказы</a>

                        <a href="{{ route('marketplace_orders.index', ['status' => 6, 'marketplace_id' => request('marketplace_id'), 'fulfillment_type' => request('fulfillment_type'), 'cluster' => request('cluster')]) }}"
                       class="btn btn-link mr-3 mb-3">На поставку</a>

                        <a href="{{ route('marketplace_orders.index', ['status' => 3, 'marketplace_id' => request('marketplace_id'), 'fulfillment_type' => request('fulfillment_type'), 'cluster' => request('cluster')]) }}"
                           class="btn btn-link mr-3 mb-3">Выполненные</a>
                </div>

                <div class="row">
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

                    <div class="form-group col-md-2">
                        <select name="fulfillment_type"
                                id="fulfillment_type"
                                class="form-control"
                                onchange="updatePageWithQueryParam(this)"
                                required>
                            <option value="" selected>---</option>
                            <option value="fbo"
                                    @if(request()->get('fulfillment_type') == 'fbo') selected @endif>
                                FBO
                            </option>
                            <option value="fbs"
                                    @if(request()->get('fulfillment_type') == 'fbs') selected @endif>
                                FBS
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        {{-- OZON кластеры --}}
                        <select name="cluster"
                                id="cluster_ozon"
                                class="form-control cluster-select"
                                style="display: none;"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">---</option>
                            <option value="Алматы"
                                    @if(request()->get('cluster') == 'Алматы') selected @endif>
                                Алматы
                            </option>
                            <option value="Астана"
                                    @if(request()->get('cluster') == 'Астана') selected @endif>
                                Астана
                            </option>
                            <option value="Беларусь"
                                    @if(request()->get('cluster') == 'Беларусь') selected @endif>
                                Беларусь
                            </option>
                            <option value="Воронеж"
                                    @if(request()->get('cluster') == 'Воронеж') selected @endif>
                                Воронеж
                            </option>
                            <option value="Дальний Восток"
                                    @if(request()->get('cluster') == 'Дальний Восток') selected @endif>
                                Дальний Восток
                            </option>
                            <option value="Екатеринбург"
                                    @if(request()->get('cluster') == 'Екатеринбург') selected @endif>
                                Екатеринбург
                            </option>
                            <option value="Казань"
                                    @if(request()->get('cluster') == 'Казань') selected @endif>
                                Казань
                            </option>
                            <option value="Калининград"
                                    @if(request()->get('cluster') == 'Калининград') selected @endif>
                                Калининград
                            </option>
                            <option value="Краснодар"
                                    @if(request()->get('cluster') == 'Краснодар') selected @endif>
                                Краснодар
                            </option>
                            <option value="Красноярск"
                                    @if(request()->get('cluster') == 'Красноярск') selected @endif>
                                Красноярск
                            </option>
                            <option value="Махачкала"
                                    @if(request()->get('cluster') == 'Махачкала') selected @endif>
                                Махачкала
                            </option>
                            <option value="Москва, МО и Дальние регионы"
                                    @if(request()->get('cluster') == 'Москва, МО и Дальние регионы') selected @endif>
                                Москва, МО и Дальние регионы
                            </option>
                            <option value="Невинномысск"
                                    @if(request()->get('cluster') == 'Невинномысск') selected @endif>
                                Невинномысск
                            </option>
                            <option value="Новосибирск"
                                    @if(request()->get('cluster') == 'Новосибирск') selected @endif>
                                Новосибирск
                            </option>
                            <option value="Омск"
                                    @if(request()->get('cluster') == 'Омск') selected @endif>
                                Омск
                            </option>
                            <option value="Оренбург"
                                    @if(request()->get('cluster') == 'Оренбург') selected @endif>
                                Оренбург
                            </option>
                            <option value="Пермь"
                                    @if(request()->get('cluster') == 'Пермь') selected @endif>
                                Пермь
                            </option>
                            <option value="Ростов"
                                    @if(request()->get('cluster') == 'Ростов') selected @endif>
                                Ростов
                            </option>
                            <option value="Самара"
                                    @if(request()->get('cluster') == 'Самара') selected @endif>
                                Самара
                            </option>
                            <option value="Санкт-Петербург и СЗО"
                                    @if(request()->get('cluster') == 'Санкт-Петербург и СЗО') selected @endif>
                                Санкт-Петербург и СЗО
                            </option>
                            <option value="Саратов"
                                    @if(request()->get('cluster') == 'Саратов') selected @endif>
                                Саратов
                            </option>
                            <option value="Тверь"
                                    @if(request()->get('cluster') == 'Тверь') selected @endif>
                                Тверь
                            </option>
                            <option value="Тюмень"
                                    @if(request()->get('cluster') == 'Тюмень') selected @endif>
                                Тюмень
                            </option>
                            <option value="Уфа"
                                    @if(request()->get('cluster') == 'Уфа') selected @endif>
                                Уфа
                            </option>
                            <option value="Ярославль"
                                    @if(request()->get('cluster') == 'Ярославль') selected @endif>
                                Ярославль
                            </option>
                        </select>

                        {{-- WB кластеры --}}
                        <select name="cluster"
                                id="cluster_wb"
                                class="form-control cluster-select"
                                style="display: none;"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">---</option>
                            <option value="Алексин (Тула)"
                                    @if(request()->get('cluster') == 'Алексин (Тула)') selected @endif>
                                Алексин (Тула)
                            </option>
                            <option value="Владимир (Воршинское)"
                                    @if(request()->get('cluster') == 'Владимир (Воршинское)') selected @endif>
                                Владимир (Воршинское)
                            </option>
                            <option value="Волгоград"
                                    @if(request()->get('cluster') == 'Волгоград') selected @endif>
                                Волгоград
                            </option>
                            <option value="Екатеринбург (Испытателей)"
                                    @if(request()->get('cluster') == 'Екатеринбург (Испытателей)') selected @endif>
                                Екатеринбург (Испытателей)
                            </option>
                            <option value="Екатеринбург (Перспективный)"
                                    @if(request()->get('cluster') == 'Екатеринбург (Перспективный)') selected @endif>
                                Екатеринбург (Перспективный)
                            </option>
                            <option value="Казань"
                                    @if(request()->get('cluster') == 'Казань') selected @endif>
                                Казань
                            </option>
                            <option value="Коледино"
                                    @if(request()->get('cluster') == 'Коледино') selected @endif>
                                Коледино
                            </option>
                            <option value="Котовск"
                                    @if(request()->get('cluster') == 'Котовск') selected @endif>
                                Котовск
                            </option>
                            <option value="Краснодар"
                                    @if(request()->get('cluster') == 'Краснодар') selected @endif>
                                Краснодар
                            </option>
                            <option value="Невинномысск"
                                    @if(request()->get('cluster') == 'Невинномысск') selected @endif>
                                Невинномысск
                            </option>
                            <option value="Нижний Новгород"
                                    @if(request()->get('cluster') == 'Нижний Новгород') selected @endif>
                                Нижний Новгород
                            </option>
                            <option value="Новосибирск(Петухова)"
                                    @if(request()->get('cluster') == 'Новосибирск(Петухова)') selected @endif>
                                Новосибирск(Петухова)
                            </option>
                            <option value="Рязань"
                                    @if(request()->get('cluster') == 'Рязань') selected @endif>
                                Рязань
                            </option>
                            <option value="Самара (Новосемейкино)"
                                    @if(request()->get('cluster') == 'Самара (Новосемейкино)') selected @endif>
                                Самара (Новосемейкино)
                            </option>
                            <option value="Санкт-Петербург(Уткина Заводь)"
                                    @if(request()->get('cluster') == 'Санкт-Петербург(Уткина Заводь)') selected @endif>
                                Санкт-Петербург(Уткина Заводь)
                            </option>
                            <option value="Санкт-Петербург(Шушары)"
                                    @if(request()->get('cluster') == 'Санкт-Петербург(Шушары)') selected @endif>
                                Санкт-Петербург(Шушары)
                            </option>
                            <option value="Сарапул"
                                    @if(request()->get('cluster') == 'Сарапул') selected @endif>
                                Сарапул
                            </option>
                            <option value="Электросталь"
                                    @if(request()->get('cluster') == 'Электросталь') selected @endif>
                                Электросталь
                            </option>
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

                                @if($order->cluster)
                                    Кластер:<b> {{ $order->cluster }} </b>
                                @endif

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
                                    @if($order->completed_at)
                                        <div class="mt-2">
                                            <small class="mr-2">
                                                Выполнен:
                                                <b> {{ now()->parse($order->completed_at)->format('d/m/Y H:i') }}</b>
                                            </small>
                                        </div>
                                    @endif
                                    @if(request()->status == 3)
                                        <td>
                                            @isset($order->supply_id)
                                                <a href="{{ route('marketplace_supplies.show', ['marketplace_supply' => $order->supply_id]) }}">
                                                    {{ $order->supply->supply_id ?? 'поставка: № ' . $order->supply_id }}
                                                </a>
                                            @endisset
                                        </td>
                                    @endif
                                </div>

                                @if(auth()->user()->isAdmin())
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
                            @if(request()->status == 3)
                                <th scope="col">Поставка</th>
                            @endif
                            <th scope="col">Маркетплейс</th>
                            <th scope="col">Тип</th>
                            <th scope="col">Кластер</th>
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
                                @if(request()->status == 3)
                                    <td>
                                        @isset($order->supply_id)
                                            <a href="{{ route('marketplace_supplies.show', ['marketplace_supply' => $order->supply_id]) }}">
                                                {{ $order->supply->supply_id ?? 'поставка: № ' . $order->supply_id }}
                                            </a>
                                        @endisset
                                    </td>
                                @endif
                                <td>
                                    <img style="width: 80px;"
                                         src="{{ asset($order->marketplace_name) }}"
                                         alt="{{ $order->marketplace_name }}">
                                </td>
                                <td>{{ $order->fulfillment_type }}</td>
                                <td>{{ $order->cluster }}</td>
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
                                    @if(auth()->user()->isAdmin())
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
    <link
        href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
        rel="stylesheet"/>
    <link href="{{ asset('css/marketplace_orders.css') }}" rel="stylesheet"/>
@endpush

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            const marketplaceSelect = $('#marketplace_id');
            const fulfillmentTypeSelect = $('#fulfillment_type');
            const clusterOzon = $('#cluster_ozon');
            const clusterWb = $('#cluster_wb');

            // Инициализируем Select2 для кластеров
            clusterOzon.select2({width: '100%'});
            clusterWb.select2({width: '100%'});

            // Сначала скрываем оба Select2 контейнера
            clusterOzon.next('.select2').hide();
            clusterWb.next('.select2').hide();

            function toggleClusters() {
                const marketplaceId = marketplaceSelect.val();
                const fulfillmentType = fulfillmentTypeSelect.val();

                // Скрываем оба Select2 контейнера
                clusterOzon.next('.select2').hide();
                clusterWb.next('.select2').hide();

                // Показываем только если выбран FBO и маркетплейс
                if (fulfillmentType === 'fbo' && marketplaceId) {
                    if (marketplaceId == 1) { // OZON
                        clusterOzon.next('.select2').show();
                    } else if (marketplaceId == 2) { // WB
                        clusterWb.next('.select2').show();
                    }
                }
            }

            // Инициализация при загрузке
            toggleClusters();

            // Переключение маркетплейса или типа
            marketplaceSelect.on('change', toggleClusters);
            fulfillmentTypeSelect.on('change', toggleClusters);
        });
    </script>
@endpush
