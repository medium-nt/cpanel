@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Редактирование поставки WB FBO
                    #{{ $supply->supply_id }}</h3>
            </div>
            <div class="card-body">
                <form
                    action="{{ route('marketplace_supplies.update_wb_fbo', ['marketplace_supply' => $supply]) }}"
                    method="POST">
                    @csrf
                    @method('PUT')

                    <div class="d-flex align-items-start mb-3">
                        <div class="form-group mr-3 mb-0">
                            <label for="gazelka_shipment_id">ID отгрузки в
                                Газельку</label>
                            <input type="text" class="form-control"
                                   id="gazelka_shipment_id"
                                   name="gazelka_shipment_id"
                                   value="{{ old('gazelka_shipment_id', $supply->gazelka_shipment_id) }}">
                        </div>

                        <div class="form-group mb-0">
                            <label for="gazelka_shipment_date">Дата
                                отгрузки</label>
                            <input type="date" class="form-control"
                                   id="gazelka_shipment_date"
                                   name="gazelka_shipment_date"
                                   value="{{ old('gazelka_shipment_date', $supply->gazelka_shipment_date?->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mr-2">
                        Сохранить
                    </button>
                    <a href="{{ route('marketplace_supplies.show', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-link">Отмена</a>
                </form>
            </div>
        </div>
    </div>
@stop
