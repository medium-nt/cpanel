@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12 col-lg-12">
        <div class="card">

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('marketplace_orders.store') }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="order_id">Номер заявки</label>
                                <input type="text"
                                       class="form-control @error('order_id') is-invalid @enderror"
                                       id="order_id"
                                       name="order_id"
                                       placeholder=""
                                       value="{{ old('order_id') }}"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="marketplace_id">Маркетплейс</label>
                                <select name="marketplace_id" id="marketplace_id" class="form-control" required>
                                    <option value="" disabled selected>---</option>
                                    <option value="1" @if(old('marketplace_id') == 1) selected @endif>OZON</option>
                                    <option value="2" @if(old('marketplace_id') == 2) selected @endif>WB</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="fulfillment_type">Тип</label>
                                <select name="fulfillment_type" id="fulfillment_type" class="form-control" required>
                                    <option value="" disabled selected>---</option>
                                    <option value="FBO" @if(old('fulfillment_type') == 'FBO') selected @endif>FBO</option>
                                    <option value="FBS" @if(old('fulfillment_type') == 'FBS') selected @endif>FBS</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="cluster">Кластер (только для
                                    FBO)</label>
                                <select name="cluster" id="cluster"
                                        class="form-control" required>
                                    <option value="" disabled selected>---
                                    </option>
                                    <option value="Алматы"
                                            @if(old('cluster') == 'Алматы') selected @endif>
                                        Алматы
                                    </option>
                                    <option value="Астана"
                                            @if(old('cluster') == 'Астана') selected @endif>
                                        Астана
                                    </option>
                                    <option value="Беларусь"
                                            @if(old('cluster') == 'Беларусь') selected @endif>
                                        Беларусь
                                    </option>
                                    <option value="Воронеж"
                                            @if(old('cluster') == 'Воронеж') selected @endif>
                                        Воронеж
                                    </option>
                                    <option value="Дальний Восток"
                                            @if(old('cluster') == 'Дальний Восток') selected @endif>
                                        Дальний Восток
                                    </option>
                                    <option value="Екатеринбург"
                                            @if(old('cluster') == 'Екатеринбург') selected @endif>
                                        Екатеринбург
                                    </option>
                                    <option value="Казань"
                                            @if(old('cluster') == 'Казань') selected @endif>
                                        Казань
                                    </option>
                                    <option value="Калининград"
                                            @if(old('cluster') == 'Калининград') selected @endif>
                                        Калининград
                                    </option>
                                    <option value="Краснодар"
                                            @if(old('cluster') == 'Краснодар') selected @endif>
                                        Краснодар
                                    </option>
                                    <option value="Красноярск"
                                            @if(old('cluster') == 'Красноярск') selected @endif>
                                        Красноярск
                                    </option>
                                    <option value="Махачкала"
                                            @if(old('cluster') == 'Махачкала') selected @endif>
                                        Махачкала
                                    </option>
                                    <option value="Москва, МО и Дальние регионы"
                                            @if(old('cluster') == 'Москва, МО и Дальние регионы') selected @endif>
                                        Москва, МО и Дальние регионы
                                    </option>
                                    <option value="Невинномысск"
                                            @if(old('cluster') == 'Невинномысск') selected @endif>
                                        Невинномысск
                                    </option>
                                    <option value="Новосибирск"
                                            @if(old('cluster') == 'Новосибирск') selected @endif>
                                        Новосибирск
                                    </option>
                                    <option value="Омск"
                                            @if(old('cluster') == 'Омск') selected @endif>
                                        Омск
                                    </option>
                                    <option value="Оренбург"
                                            @if(old('cluster') == 'Оренбург') selected @endif>
                                        Оренбург
                                    </option>
                                    <option value="Пермь"
                                            @if(old('cluster') == 'Пермь') selected @endif>
                                        Пермь
                                    </option>
                                    <option value="Ростов"
                                            @if(old('cluster') == 'Ростов') selected @endif>
                                        Ростов
                                    </option>
                                    <option value="Самара"
                                            @if(old('cluster') == 'Самара') selected @endif>
                                        Самара
                                    </option>
                                    <option value="Санкт-Петербург и СЗО"
                                            @if(old('cluster') == 'Санкт-Петербург и СЗО') selected @endif>
                                        Санкт-Петербург и СЗО
                                    </option>
                                    <option value="Саратов"
                                            @if(old('cluster') == 'Саратов') selected @endif>
                                        Саратов
                                    </option>
                                    <option value="Тверь"
                                            @if(old('cluster') == 'Тверь') selected @endif>
                                        Тверь
                                    </option>
                                    <option value="Тюмень"
                                            @if(old('cluster') == 'Тюмень') selected @endif>
                                        Тюмень
                                    </option>
                                    <option value="Уфа"
                                            @if(old('cluster') == 'Уфа') selected @endif>
                                        Уфа
                                    </option>
                                    <option value="Ярославль"
                                            @if(old('cluster') == 'Ярославль') selected @endif>
                                        Ярославль
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-9">
                            <div class="form-group">
                                <label for="item_id">Товар</label>
                                <select name="item_id[]"
                                        id="item_id"
                                        class="form-control item_id"
                                        required>
                                    <option value="" disabled selected>---</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" @if(old('item_id') == $item->id) selected @endif>
                                            {{ $item->title }} {{ $item->width }}х{{ $item->height }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="quantity">Количество</label>
                                <input type="number"
                                       class="form-control @error('quantity') is-invalid @enderror"
                                       id="quantity"
                                       name="quantity[]"
                                       step="1"
                                       disabled
                                >
                            </div>
                        </div>
                    </div>

                    @for($i = 1; $i < 1; $i++)
                        <x-odred_item-component :items="$items" :i="$i"/>
                    @endfor

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Создать</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/marketplace_orders.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('.item_id').on('change', function() {
                var quantityInput = $(this).closest('.row').find('[name="quantity[]"]');

                if ($(this).val() === '') {
                    quantityInput.prop('disabled', true);
                } else {
                    quantityInput.prop('disabled', false);
                }
            });
        });
    </script>
@endpush

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link href="{{ asset('css/marketplace_orders.css') }}" rel="stylesheet"/>
@endpush
