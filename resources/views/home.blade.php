@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    @php
        $user = auth()->user();
    @endphp

    @if($user->isAdmin() && count($workshopsMissingDates) > 0)
        <div class="callout callout-danger">
            <h5><i class="icon fas fa-exclamation-triangle"></i> Расписание смен
                не заполнено</h5>
            @foreach($workshopsMissingDates as $item)
                <p class="mb-1"><strong>{{ $item['workshop']->title }}:</strong>
                </p>
                <div class="mb-2">
                    @foreach($item['dates'] as $date)
                        <span class="badge bg-danger mr-2 mb-1"
                              style="font-size: 0.85rem;">
                            {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}
                        </span>
                    @endforeach
                </div>
            @endforeach
            <a href="{{ route('shift-schedule.index') }}"
               class="btn btn-sm btn-outline-danger">
                <i class="fas fa-calendar-alt"></i> Перейти к расписанию
            </a>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="row">
                {{-- Раскроено --}}
                @if($user->isOtk())
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                        <a href="{{ route('marketplace_order_items.index', ['status' => 'new']) }}"
                           class="link-black">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i
                                        class="fas fa-toilet-paper"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Раскроено</span>
                                    <span
                                        class="info-box-number">{{ $cutMarketplaceOrderItem }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif

                {{-- Новые задания на пошив --}}
                @if($user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter() || $user->isManager())
                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                    <a href="{{ route('marketplace_order_items.index', ['status' => 'new']) }}" class="link-black">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-cart-plus"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Новые задания на пошив</span>
                                <span class="info-box-number">{{ $newMarketplaceOrderItem }}</span>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                {{-- Товары в пошиве --}}
                @if($user->isSeamstress() || $user->isAdmin() || $user->isStorekeeper())
                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                    <a href="{{ route('marketplace_order_items.index') }}" class="link-black">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-tags"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Товары в пошиве</span>
                                <span class="info-box-number">{{ $marketplaceOrderItemInWork }}</span>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                {{-- Товары в закрое --}}
                @if($user->isAdmin() || $user->isStorekeeper() || $user->isCutter())
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                    <a href="{{ route('marketplace_order_items.index') }}" class="link-black">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-toilet-paper"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Товары в закрое</span>
                                <span class="info-box-number">{{ $marketplaceOrderItemInCutting }}</span>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                {{-- Срочные заказы (FBS) --}}
                @if($user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter())
                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                    <a href="{{ route('marketplace_order_items.index', ['status' => 'new']) }}" class="link-black">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-bolt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Срочные заказы (FBS)</span>
                                <span class="info-box-number">{{ $urgentMarketplaceOrderItem }}</span>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                {{-- Товары к подбору со склада --}}
                @if($user->isAdmin() || $user->isStorekeeper())
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                        <a href="{{ route('warehouse_of_item.to_pick_list') }}"
                           class="link-black">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i
                                        class="fas fa-box-open"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Товары к подбору со склада</span>
                                    <span
                                        class="info-box-number">{{ $pickupOrders }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif

                {{-- Не отгруженные поставки в цех --}}
                @if($user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter())
                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                    <a href="{{ route('movements_to_workshop.index') }}" class="link-black">
                        <div class="info-box">
                            <span class="info-box-icon bg-secondary"><i class="fas fa-dolly"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Не отгруженные поставки в цех</span>
                                <span class="info-box-number">{{ $notShippedMovements }}</span>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                {{-- Не принятые поставки в цехе --}}
                @if($user->isAdmin() || $user->isStorekeeper() || $user->isSeamstress() || $user->isCutter())
                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                    <a href="{{ route('movements_to_workshop.index') }}" class="link-black">
                        <div class="info-box">
                            <span class="info-box-icon bg-secondary"><i class="fas fa-boxes"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Не принятые поставки в цехе</span>
                                <span class="info-box-number">{{ $notReceivedMovements }}</span>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                {{-- Товары на стикеровке --}}
                @if($user->isOtk() || $user->isAdmin() || $user->isStorekeeper())
                    <div
                        class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                        <a href="{{ route('marketplace_order_items.index', ['status' => 'labeling']) }}"
                           class="link-black">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i
                                        class="fas fa-sticky-note"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Товары на стикеровке</span>
                                    <span
                                        class="info-box-number">{{ $stickeredMarketplaceOrderItem }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif

                {{-- Рулонов с малым остатком --}}
                @if($user->isAdmin() || $user->isStorekeeper())
                    <div
                        class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3">
                        <a href="/megatulle/rolls?status=unclosed"
                           class="link-black">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i
                                        class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Рулонов с малым остатком</span>
                                    <span
                                        class="info-box-number">{{ $lowMaterialRollsCount }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Отгрузки в Газельку --}}
    @if($user->isAdmin() || $user->isStorekeeper() || $user->isManager())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Отгрузки в Газельку</h3>
            </div>
            <div class="card-body">
                @if($gazelkaShipments->isNotEmpty())
                    <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th>Статус</th>
                            <th>Поставка</th>
                            <th>Отгрузка в Газельку</th>
                            <th>Отгрузка в маркетплейс</th>
                            <th>Маркетплейс</th>
                            <th>Кластер</th>
                            <th>Заказов</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($gazelkaShipments as $shipment)
                            <tr class="{{ $shipment->gazelka_shipment_date->isToday() ? 'text-danger font-weight-bold' : '' }}">
                                <td>
                                    @switch($shipment->status)
                                        @case(0)
                                            <span class="badge bg-secondary"> Открытая </span>
                                            @break
                                        @case(3)
                                            <span class="badge bg-success"> Закрытая </span>
                                            @break
                                        @case(4)
                                            <span class="badge bg-warning"> Отгрузка </span>
                                            @break
                                        @case(13)
                                            <span class="badge bg-info"> На сборке </span>
                                            @break
                                    @endswitch
                                </td>
                                <td>
                                    <a href="{{ route('marketplace_supplies.show', $shipment) }}"
                                       class="{{ $shipment->gazelka_shipment_date->isToday() ? 'text-danger font-weight-bold' : '' }}">
                                        Поставка #{{ $shipment->supply_id }}
                                    </a>
                                </td>
                                <td>
                                    {{ $shipment->gazelka_shipment_date->format('d.m.Y') }}
                                    @if($shipment->gazelka_pickup === true)
                                        <br><span class="badge badge-warning">Забор Газелькой</span>
                                    @elseif($shipment->gazelka_pickup === false)
                                        <br><span class="badge badge-danger">До склада Газельки</span>
                                    @endif
                                </td>
                                <td>{{ $shipment->supply_date->format('d.m.Y') }}</td>
                                <td>
                                    <img style="width: 80px;"
                                         src="{{ asset($shipment->marketplace_name) }}"
                                         alt="{{ $shipment->marketplace_name }}">
                                </td>
                                <td>{{ $shipment->cluster }}</td>
                                <td>{{ $shipment->marketplace_orders()->count() }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                @else
                    <p class="text-muted">Нет предстоящих отгрузок.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Начисленные штрафы сотрудникам --}}
    @if($user->isAdmin())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Начисленные штрафы сотрудникам</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Начислено за</th>
                            <th scope="col" colspan="2"
                                style="text-align: center">Сумма
                            </th>
                            <th scope="col">Название</th>
                            <th scope="col">Дата создания</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td style="width: 50px">{{ $loop->iteration }}</td>
                                <td>{{ now()->parse($transaction->accrual_for_date)->format('d/m/Y') }}</td>
                                @if($transaction->is_bonus)
                                    <td></td>
                                    <td>{{ $transaction->amount }} <i
                                            class="fas fa-star text-warning"></i>
                                    </td>
                                @else
                                    <td>{{ $transaction->amount }} <i
                                            class="fas fa-ruble-sign"></i></td>
                                    <td></td>
                                @endif
                                <td>{{ $transaction->title }}
                                    @if($transaction->user_id)
                                        ({{ $transaction->user->name ?? '---' }}
                                        )
                                    @endif
                                </td>
                                <td>{{ now()->parse($transaction->created_at)->format('d/m/Y H:i') }}</td>

                                <td style="width: 100px">
                                    @if($user->isAdmin())
                                        <div class="btn-group" role="group">
                                            <form
                                                action="{{ route('transactions.destroy', ['transaction' => $transaction->id]) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-danger"
                                                        onclick="return confirm('Вы уверены что хотите удалить данную транзакцию из системы?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <x-pagination-component :collection="$transactions"/>
            </div>
        </div>
    @endif

    {{-- Зарплата --}}
    @if(!$user->isAdmin() && $user->is_show_finance)
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Зарплата</h3>
        </div>
        <div class="card-body">
            Зарплата к выплате: <b>{{ $seamstressesCurrentSalary ?? '-' }} руб.</b>
            <br>
            Бонусы к выплате: <b>{{ $seamstressesCurrentBonus ?? '-' }} баллов.</b>
            <br>
            Бонусы в ожидании: <b>{{ $seamstressesCurrentHoldBonus ?? '-' }} баллов.</b>
        </div>
    </div>
    @endif

    {{-- Смены сотрудников --}}
    @if($user->isAdmin())
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Смены сотрудников</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-valign-middle">
                <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Смена</th>
                </tr>
                </thead>
                <tbody>
                @foreach($employees as $user)
                    <tr>
                        <td>
                            <img src="{{ asset('storage/' . $user->avatar) }}"
                                 style="width:50px; height:50px;" alt="">
                            {{ $user->short_name }}
                        </td>
                        <td>
                            @if(!$user->shift_is_open)
                                <a class="btn btn-success btn-xs"
                                   href="{{ route('open_close_work_shift_admin', ['user' => $user]) }}"
                                   onclick="return confirm('Открыть смену сотрудника?')">
                                    Открыть смену
                                </a>
                            @else
                                <div class="row">
                                    <div class="col-6">
                                        <a class="btn btn-warning btn-xs"
                                           href="{{ route('open_close_work_shift_admin', ['user' => $user]) }}"
                                           onclick="return confirm('Закрыть смену сотрудника?')">
                                            Закрыть смену
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        Начало смены: {{ Carbon\Carbon::parse($user->actual_start_work_shift)->format('H:i') }} <br>
                                        Конец смены: {{ Carbon\Carbon::parse($user->endWorkShift)->format('H:i') }}
                                        @if($user->endWorkShift < Carbon\Carbon::now())
                                            <i class="fas fa-exclamation-triangle text-danger" title="Смена должна быть уже закрыта"></i>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <x-pagination-component :collection="$employees"/>
    </div>
    @endif

    {{-- Рабочий календарь --}}
    <div class="card">
        <div class="card-header row">
            <h4 class=" my-1 mr-2">Рабочий календарь</h4>

            <div class="col-md-3">
                <select name="employee_id"
                        id="employee_id"
                        class="form-control"
                        onchange="updatePageWithQueryParam(this)"
                        required>
                    @foreach($employeesForCalendar as $employee)
                        <option value="{{ $employee->id }}"
                                @if(request('employee_id', $currentUserId) == $employee->id) selected @endif
                        >{{ $employee->short_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body">
            <div id="calendar" data-events="{{ json_encode($events) }}"></div>
        </div>
    </div>

    {{-- Динамика преобладания большими размерами --}}
    @if($user->isAdmin())
    <div class="card">
        <div class="card-header">
            Динамика преобладания большими размерами
            <i class="fas fa-info-circle ml-1" onclick="toggleSpoiler()"></i>

            @if($days_ago > 7)
            <a href="{{ route('home', ['days_ago' => max(0, $days_ago - 7)]) }}"
               class="btn btn-default btn-sm float-right">
                Вперед
                <i class="fa fa-arrow-right"></i>
            </a>
            @endif

            @if($days_ago != 0)
            <a href="{{ route('home') }}"
               class="btn btn-default btn-sm float-right mr-2">
                <i class="fa fa-dot-circle-o"></i>
                Сегодня
            </a>
            @endif

            @if($days_ago < 28)
            <a href="{{ route('home', ['days_ago' => $days_ago + 7]) }}"
               class="btn btn-default btn-sm float-right mr-2">
                <i class="fa fa-arrow-left"></i>
                Назад
            </a>
            @endif

        </div>

        <div class="card-body">
            <div id="spoilerText" style="display:none;">
                <div class="callout callout-info">
                    <h5><i class="icon fas fa-info mr-3 mb-1"></i> Индекс Доминирования</h5>
                    <p>
                        В нашей компании высоко ценится эффективность и оптимальное использование материалов при пошиве штор.
                        Для объективной оценки работы швей и выявления лидеров по рациональному крою, мы разработали систему расчета
                        "индекса доминирования размера".
                        <br>
                        Суть метода заключается в вычислении среднего метража, используемого сотрудником на одно изделие в течение
                        рабочего дня. Общий метраж, сшитый швеей за день, делится на количество готовых штор. Результат демонстрирует
                        средний размер шторы, с которым преимущественно работает данный сотрудник.
                        <br><br>
                        Например, швея, обработавшая 80 метров ткани и изготовившая 15 штор, имеет индекс 5.33 (80/15 = 5.33).
                        В то же время, другая швея, также обработавшая 80 метров, но изготовившая 25 штор, имеет индекс 3.2.
                        <br>
                        Очевидно, что вторая швея в среднем шьет шторы меньшего размера, чем первая. Более высокий индекс указывает
                        на то, что швея чаще работает с крупными заказами, требующими большей ширины ткани (от 5 до 8 метров).
                        <br>
                        Низкий индекс, напротив, свидетельствует о преобладании в работе небольших заказов. Эта система позволяет не
                        только выявлять швей, успешно справляющихся с крупными размерами, но и анализировать общую структуру заказов,
                        выявлять тенденции и оптимизировать производственный процесс для достижения максимальной эффективности.
                    </p>
                </div>

            </div>

            <div style="width:100%; margin: auto;">
                <canvas id="ratingGraph" style="width: 100%; height: 400px"></canvas>
            </div>
        </div>
    </div>
    @endif
@stop

@push('js')
    <script
        src='{{ asset('vendor/fullcalendar/index.global.min.js') }}'></script>
    <script src="{{ asset('js/fullcalendar.js') }}"></script>
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
    <script src="{{ asset('js/toggle_spoiler.js') }}"></script>

    <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>

    <script>
        window.seamstressesData = {!! $seamstresses !!};
        window.dates = {!! $dates !!};
    </script>

    @if($user->role_id == '3')
    <script src="{{ asset('js/ratingGraph.js') }}"></script>
    @endif
@endpush

@push('css')
    <link href="{{ asset('css/fullcalendar.css') }}" rel="stylesheet"/>
    <link href="{{ asset('css/link_black.css') }}" rel="stylesheet"/>

    <style>
        .info-box-number {
            font-size: 1.5rem;
        }
        .custom-shift-time {
            font-size: 0.75em;
            color: #555;
            display: block;
            word-wrap: break-word;
            white-space: normal;
            max-width: 100%;
        }
    </style>

@endpush
