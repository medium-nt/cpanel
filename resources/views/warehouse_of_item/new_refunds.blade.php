@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form action="" method="get">
                    <div class="row">
                        <div class="col-md-10 mb-3">
                            <input type="text" name="barcode"
                                   class="form-control"
                                   placeholder="Поиск"
                                   autofocus
                                   value="{{ request('barcode') }}">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100">Найти</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($message)
            <div class="card">
                <div class="card-body">
                    {{ $message }}

                    @if ($marketplace_items)
                        <br>
                        @foreach ($marketplace_items as $marketplace_item)
                            <li>
                                <a href="{{ route('warehouse_of_item.new_refunds', ['barcode' => $marketplace_item->marketplaceOrder->order_id]) }}">
                                    {{ $marketplace_item->item->title }}
                                    {{ $marketplace_item->item->width }}
                                    x {{ $marketplace_item->item->height }}
                                    (швея: {{ $marketplace_item->seamstress->name ?? '' }}
                                    )
                                </a>
                            </li>
                        @endforeach
                    @endif
                </div>
            </div>
        @endif

        @if ($marketplace_item && !$message)
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h3>
                                <b>
                                    {{ $marketplace_item->item->title }}
                                    {{ $marketplace_item->item->width }}
                                    x {{ $marketplace_item->item->height }}
                                </b>
                            </h3>
                            товар: id {{ $marketplace_item->id }} <br>
                            заказ:
                            № {{ $marketplace_item->marketplaceOrder->order_id }}
                            <br>
                            стикер
                            хранения: {{ $marketplace_item->storage_barcode }}
                            <br>
                            <br>
                            @if($marketplace_item->seamstress)
                                швея: {{ $marketplace_item->seamstress->name ?? '' }}
                            @endif
                            <br>
                            @if($marketplace_item->cutter)
                                закройщик: {{ $marketplace_item->cutter->name ?? '' }}
                            @endif
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            Причина возврата: <br> <b>{{ $returnReason }}</b>
                            <br>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <a href="{{ route('warehouse_of_item.storage_barcode', ['marketplace_items' => $marketplace_item]) }}"
                               class="btn btn-outline-primary mb-3"
                               target="_blank">печать штрих-кода
                                хранения</a><br>

                            <form
                                action="{{ route('warehouse_of_item.save_storage', ['marketplace_item' => $marketplace_item]) }}"
                                method="post">
                                @csrf
                                <div class="form-group row">
                                    <div class="col-md-8">
                                        <select name="shelf_id" id="shelf_id"
                                                class="form-control">
                                            <option value="" disabled selected>
                                                Выбрать полку...
                                            </option>
                                            @foreach($shelves as $shelf)
                                                <option
                                                    value="{{ $shelf->id }}">{{ $shelf->title }}</option>
                                            @endforeach
                                        </select><br>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit"
                                                class="btn btn-primary">
                                            Сохранить
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <button type="submit"
                                        class="btn btn-warning mb-3 w-50">на
                                    осмотр
                                </button>
                                <br>
                                <button type="submit"
                                        class="btn btn-danger mb-3 w-50">брак
                                </button>
                                <br>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@stop
