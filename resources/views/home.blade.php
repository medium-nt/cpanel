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

@stop

@push('js')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js'></script>
    <script src="{{ asset('js/fullcalendar.js') }}"></script>
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush

@push('css')
    <link href="{{ asset('css/fullcalendar.css') }}" rel="stylesheet"/>
    <link href="{{ asset('css/link_black.css') }}" rel="stylesheet"/>

@endpush
