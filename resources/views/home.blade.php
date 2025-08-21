@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
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

                <div class="col-md-2">
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

                <div class="col-md-2">
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

                <div class="col-md-3">
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

                <div class="col-md-3">
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
            </div>
        </div>
    </div>

{{--    <div class="card">--}}
{{--        <div class="card-header">--}}
{{--            <h3 class="card-title">Зарплата</h3>--}}
{{--        </div>--}}
{{--        <div class="card-body">--}}
{{--            Ваша зарплата: <b>{{ $seamstressesCurrentSalary ?? '-' }} руб.</b> (к выплате)--}}
{{--            <br>--}}
{{--            Ваши бонусы: <b>{{ $seamstressesCurrentBonus ?? '-' }} баллов.</b> (в ожидании разморозки)--}}
{{--        </div>--}}
{{--    </div>--}}

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Рейтинг</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-valign-middle">
                <thead>
                <tr>
                    <th>Швея</th>
                    <th>Рейтинг</th>
                </tr>
                </thead>
                <tbody>
                @foreach($seamstressesRating as $seamstress)
                    <tr>
                        <td style="max-width: 200px">
                            <img src="{{ asset('storage/' . $seamstress->avatar) }}"
                                 style="width:50px; height:50px;" alt="">

                            {{ $seamstress->name }}
                        </td>
                        <td style="max-width: 200px">
                            за сегодня: <b>{{ $seamstress->ratingNow }}</b>
                            <br>
                            за 2 недели: <b>{{ $seamstress->rating2week }}</b>
                            <br>
                            за месяц: <b>{{ $seamstress->rating1month }}</b>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header row">
            <h4 class=" my-1 mr-2">Рабочий календарь</h4>

            <div class="col-md-3">
                <select name="employee_id"
                        id="employee_id"
                        class="form-control"
                        onchange="updatePageWithQueryParam(this)"
                        required>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}"
                                @if(request('employee_id', $currentUserId) == $employee->id) selected @endif
                        >{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body">
            <div id="calendar" data-events="{{ json_encode($events) }}"></div>
        </div>
    </div>

    @if(auth()->user()->role_id == '3')
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
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js'></script>
    <script src="{{ asset('js/fullcalendar.js') }}"></script>
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
    <script src="{{ asset('js/toggle_spoiler.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        window.seamstressesData = {!! $seamstresses !!};
        window.dates = {!! $dates !!};
    </script>
    <script src="{{ asset('js/ratingGraph.js') }}"></script>
@endpush

@push('css')
    <link href="{{ asset('css/fullcalendar.css') }}" rel="stylesheet"/>
    <link href="{{ asset('css/link_black.css') }}" rel="stylesheet"/>

    <style>
        .info-box-number {
            font-size: 1.5rem;
        }
    </style>


@endpush
