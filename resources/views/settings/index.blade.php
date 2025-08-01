@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')

    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-12">
                        <form action="{{ route('setting.save') }}" method="POST">
                            @method('POST')
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-2">
                                    <label for="title">Начало рабочего дня</label>
                                    <input
                                        type="time"
                                        class="form-control"
                                        id="working_day_start"
                                        name="working_day_start"
                                        value="{{ $settings->working_day_start }}"
                                        required
                                    >
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="title">Конец рабочего дня</label>
                                    <input
                                        type="time"
                                        class="form-control"
                                        id="working_day_end"
                                        name="working_day_end"
                                        value="{{ $settings->working_day_end }}"
                                        required
                                    >
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="title">Расписание включено?</label>
                                    <select name="is_enabled_work_schedule" id="is_enabled_work_schedule" class="form-control">
                                        <option value="1" {{ $settings->is_enabled_work_schedule == 1 ? 'selected' : '' }}>Да</option>
                                        <option value="0" {{ $settings->is_enabled_work_schedule == 0 ? 'selected' : '' }}>Нет</option>
                                    </select>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="title">WB api key</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="api_key_wb"
                                        name="api_key_wb"
                                        value="{{ $settings->api_key_wb }}"
                                    >
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="title">OZON seller id</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="seller_id_ozon"
                                        name="seller_id_ozon"
                                        value="{{ $settings->seller_id_ozon }}"
                                    >
                                </div>

                                <div class="form-group col-md-10">
                                    <label for="title">OZON api key</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="api_key_ozon"
                                        name="api_key_ozon"
                                        value="{{ $settings->api_key_ozon }}"
                                    >
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="form-group col-md-2">
                                    <label for="title">Макс. кол-во заказов у швеи</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="max_quantity_orders_to_seamstress"
                                        name="max_quantity_orders_to_seamstress"
                                        value="{{ $settings->max_quantity_orders_to_seamstress }}"
                                    >
                                </div>
                            </div>
                            <hr>

                            <button type="submit" class="btn btn-primary">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <a href="{{ route('marketplace_api.checkDuplicateSkuz') }}"
                           class="btn btn-outline-primary mb-3 mr-3">
                            Проверить дубли skuz в системе
                        </a>

                        <a href="{{ route('marketplace_api.checkSkuz') }}"
                           class="btn btn-outline-primary mb-3">
                            Проверить наличие всех skuz в системе
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

