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
