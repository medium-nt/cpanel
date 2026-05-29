@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-8">
        <div class="card">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                action="{{ route('marketplace_order_items.generate_sticker_tape') }}"
                method="POST" target="_blank">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="item_id">Товар</label>
                                <select name="item_id" id="item_id"
                                        class="form-control" required>
                                    <option value="" disabled selected>---
                                    </option>
                                    @foreach($items as $item)
                                        <option
                                            value="{{ $item->id }}">{{ $item->title }} {{ $item->width }}
                                            х{{ $item->height }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="marketplace_id">Маркетплейс</label>
                                <select name="marketplace_id"
                                        id="marketplace_id" class="form-control"
                                        required>
                                    <option value="" disabled selected>---
                                    </option>
                                    <option value="1">OZON</option>
                                    <option value="2">WB</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="cluster">Кластер</label>
                                <select name="cluster" id="cluster"
                                        class="form-control" disabled>
                                    <option value="">---</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="cutter_id">Закройщик</label>
                                <select name="cutter_id" id="cutter_id"
                                        class="form-control">
                                    <option value="">---</option>
                                    @foreach($cutters as $cutter)
                                        <option
                                            value="{{ $cutter->id }}">{{ $cutter->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="seamstress_id">Швея</label>
                                <select name="seamstress_id" id="seamstress_id"
                                        class="form-control">
                                    <option value="">---</option>
                                    @foreach($seamstresses as $seamstress)
                                        <option
                                            value="{{ $seamstress->id }}">{{ $seamstress->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            Сгенерировать стикер
                        </button>
                        <a href="{{ route('marketplace_order_items.sticker_tape_import') }}"
                           class="btn btn-outline-primary">
                            <i class="fas fa-file-excel"></i> Загрузить из Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('css')
    <link
        href="{{ asset('vendor/select2/select2.min.css') }}"
        rel="stylesheet"/>
    <style>
        .select2-container--default .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding: 0.375rem 0.75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + 0.75rem + 2px);
        }
    </style>
@endpush

@push('js')
    <script
        src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script>
        $('#item_id').select2({
            width: '100%',
            dropdownAutoWidth: true,
            placeholder: '-- Выберите товар --',
        });

        const warehouses = @json($warehouses);

        function initClusterSelect() {
            const el = document.getElementById('cluster');
            if (!el) return;
            if (el.classList.contains('select2-hidden-accessible')) {
                $(el).select2('destroy');
            }
            $(el).select2({
                width: '100%',
                dropdownAutoWidth: true,
                placeholder: '-- Выберите кластер --',
            });
        }

        document.getElementById('marketplace_id').addEventListener('change', function () {
            const clusterSelect = document.getElementById('cluster');
            clusterSelect.innerHTML = '';

            const marketplaceId = this.value;

            if (!marketplaceId || !warehouses[marketplaceId]) {
                clusterSelect.innerHTML = '<option value="">---</option>';
                clusterSelect.disabled = true;
                if (clusterSelect.classList.contains('select2-hidden-accessible')) {
                    $(clusterSelect).select2('destroy');
                }
                return;
            }

            clusterSelect.disabled = false;
            clusterSelect.innerHTML = '<option value="">---</option>';

            for (const [name, label] of Object.entries(warehouses[marketplaceId])) {
                const option = document.createElement('option');
                option.value = name;
                option.textContent = label;
                clusterSelect.appendChild(option);
            }

            initClusterSelect();
        });
    </script>
@endpush
