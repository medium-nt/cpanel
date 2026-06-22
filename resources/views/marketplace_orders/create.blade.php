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
                                       maxlength="15"
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

                        {{-- Кластер --}}
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="cluster_ozon">Кластер (только для
                                    FBO)</label>

                                {{-- OZON --}}
                                <select name="cluster" id="cluster_ozon"
                                        class="form-control cluster-select"
                                        disabled>
                                    <option value="" disabled selected>---
                                    </option>
                                    @foreach($warehouses[1] ?? [] as $value => $label)
                                        <option value="{{ $value }}"
                                                @if(old('cluster') == $value) selected @endif>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                {{-- WB --}}
                                <select name="cluster" id="cluster_wb"
                                        class="form-control cluster-select"
                                        disabled>
                                    <option value="" disabled selected>---
                                    </option>
                                    @foreach($warehouses[2] ?? [] as $value => $label)
                                        <option value="{{ $value }}"
                                                @if(old('cluster') == $value) selected @endif>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-9">
                            <div class="form-group">
                                <label for="item_id0">Товар</label>
                                <select name="item_id[]"
                                        id="item_id0"
                                        class="form-control item_id"
                                        required>
                                    <option value="" disabled selected>---</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}"
                                                @if(old('item_id.0') == $item->id) selected @endif>
                                            {{ $item->title }} {{ $item->width }}х{{ $item->height }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="quantity0">Количество</label>
                                <input type="number"
                                       class="form-control @error('quantity.0') is-invalid @enderror"
                                       id="quantity0"
                                       name="quantity[]"
                                       step="1"
                                       min="1"
                                       disabled
                                       @if(old('quantity.0')) value="{{ old('quantity.0') }}" @endif
                                >
                            </div>
                        </div>
                    </div>

                    <div id="fbo-additional-rows">
                        @for($i = 1; $i < 20; $i++)
                        <x-odred_item-component :items="$items" :i="$i"/>
                    @endfor
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Создать</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@push('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('js/marketplace_orders.js') }}"></script>

    <script>
        $(document).ready(function() {
            const fulfillmentTypeSelect = $('#fulfillment_type');
            const fboAdditionalRows = $('#fbo-additional-rows');
            let clusterSelect = $('#cluster_ozon');

            function toggleFboMode(isFbo) {
                if (isFbo) {
                    fboAdditionalRows.show();
                    clusterSelect.prop('disabled', false).prop('required', true);
                } else {
                    fboAdditionalRows.hide();
                    clusterSelect.prop('disabled', true).prop('required', false).val('').trigger('change');
                    // Очистить дополнительные поля
                    fboAdditionalRows.find('select').val('').trigger('change');
                    fboAdditionalRows.find('input[type="number"]').val('').prop('disabled', true);
                }
            }

            // Инициализация при загрузке
            toggleFboMode(fulfillmentTypeSelect.val() === 'FBO');

            // Обработка изменения типа
            fulfillmentTypeSelect.on('change', function () {
                toggleFboMode($(this).val() === 'FBO');
            });

            // Переключение кластеров в зависимости от маркетплейса
            const marketplaceSelect = $('#marketplace_id');
            const clusterOzon = $('#cluster_ozon');
            const clusterWb = $('#cluster_wb');

            // Инициализируем Select2 для кластеров
            clusterOzon.select2();
            clusterWb.select2();

            // Сначала скрываем оба Select2 контейнера
            clusterOzon.next('.select2').hide();
            clusterWb.next('.select2').hide();
            clusterOzon.prop('disabled', true);
            clusterWb.prop('disabled', true);

            function toggleClusterByMarketplace(marketplaceId) {
                // Скрываем оба Select2 контейнера
                clusterOzon.next('.select2').hide();
                clusterWb.next('.select2').hide();
                clusterOzon.prop('disabled', true);
                clusterWb.prop('disabled', true);

                // Показываем нужный
                if (marketplaceId == 1) { // OZON
                    clusterOzon.next('.select2').show();
                    clusterOzon.prop('disabled', false);
                    clusterSelect = clusterOzon;
                } else if (marketplaceId == 2) { // WB
                    clusterWb.next('.select2').show();
                    clusterWb.prop('disabled', false);
                    clusterSelect = clusterWb;
                }
            }

            // Инициализация при загрузке
            const initialMarketplace = marketplaceSelect.val();
            if (initialMarketplace) {
                toggleClusterByMarketplace(initialMarketplace);
            }

            // Переключение маркетплейса
            marketplaceSelect.on('change', function () {
                const newValue = $(this).val();
                toggleClusterByMarketplace(newValue);

                // Сбросить выбранное значение
                clusterSelect.val('').trigger('change');

                // Обновить FBO/FBS состояние
                toggleFboMode($('#fulfillment_type').val() === 'FBO');
            });

            const itemSelects = $('.item_id');

            // Логика активации quantity
            function initQuantityState() {
                itemSelects.each(function () {
                    const quantityInput = $(this).closest('.row').find('[name="quantity[]"]');
                    if (!$(this).val()) {
                        quantityInput.prop('disabled', true);
                    } else {
                        quantityInput.prop('disabled', false);
                    }
                });
            }

            // Инициализация при загрузке (для old()) - с задержкой после Select2
            setTimeout(function () {
                initQuantityState();
            }, 100);

            itemSelects.on('change', function () {
                const quantityInput = $(this).closest('.row').find('[name="quantity[]"]');
                if ($(this).val() === '') {
                    quantityInput.prop('disabled', true).val('');
                } else {
                    quantityInput.prop('disabled', false);
                }
            });
        });
    </script>
@endpush

@push('css')
    <link href="{{ asset('vendor/select2/select2.min.css') }}"
          rel="stylesheet"/>
    <link href="{{ asset('css/marketplace_orders.css') }}" rel="stylesheet"/>
@endpush
