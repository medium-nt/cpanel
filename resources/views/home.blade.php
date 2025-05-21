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

    <script>
        function toggleSpoiler() {
            var spoiler = document.getElementById('spoilerText');
            if (spoiler.style.display === 'none') {
                spoiler.style.display = 'block';
            } else {
                spoiler.style.display = 'none';
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('ratingGraph').getContext('2d');

        const seamstressesData = {!! $seamstresses !!};

        const datasets = Object.keys(seamstressesData).map(seamstressId => {
            return {
                label: seamstressesData[seamstressId].name,
                data: Object.keys(seamstressesData[seamstressId]).map(date => seamstressesData[seamstressId][date]).slice(1),
                cubicInterpolationMode: 'monotone'
            };
        });

        const data = {
            labels: {!! $dates !!},
            datasets: datasets
        };

        new Chart(ctx, {
            type: 'line',
            data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        min: 1,
                        max: 8
                    }
                },
                ticks: {
                    stepSize: 1
                }
            }
        });
    </script>
@endpush

@push('css')
    <link href="{{ asset('css/fullcalendar.css') }}" rel="stylesheet"/>
    <link href="{{ asset('css/link_black.css') }}" rel="stylesheet"/>

@endpush
