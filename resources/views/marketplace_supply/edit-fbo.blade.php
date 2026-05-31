@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $title }}
                    #{{ $supply->supply_id }}</h3>
            </div>
            <div class="card-body">
                <form
                    action="{{ route('marketplace_supplies.update_fbo', ['marketplace_supply' => $supply]) }}"
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

                        <div class="form-group ml-3 mb-0">
                            <label for="delivery_type">Тип поставки</label>
                            <select class="form-control"
                                    id="delivery_type"
                                    name="delivery_type">
                                <option value="">-- Не указан --</option>
                                @foreach(\App\Models\MarketplaceSupply::DELIVERY_TYPES as $type)
                                    <option value="{{ $type }}"
                                        {{ old('delivery_type', $supply->delivery_type) === $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group ml-3 mb-0">
                            @php($pickupVal = old('gazelka_pickup', $supply->gazelka_pickup))
                            <label for="gazelka_pickup">Забор Газелькой</label>
                            <select class="form-control"
                                    id="gazelka_pickup"
                                    name="gazelka_pickup">
                                <option value="">-- Не указан --</option>
                                <option value="1"
                                    {{ $pickupVal === true || $pickupVal === 1 || $pickupVal === '1' ? 'selected' : '' }}>
                                    Да
                                </option>
                                <option value="0"
                                    {{ $pickupVal === false || $pickupVal === 0 || $pickupVal === '0' ? 'selected' : '' }}>
                                    Нет
                                </option>
                            </select>
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
