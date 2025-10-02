@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('warehouse_of_item.index') }}" method="get">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <select name="status" id="status" class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">Все</option>
                            <option value="9"
                                    @if(request()->get('status') == 9) selected @endif>
                                Возврат с маркетплейса
                            </option>
                            <option value="10"
                                    @if(request()->get('status') == 10) selected @endif>
                                На разборе
                            </option>
                            <option value="11"
                                    @if(request()->get('status') == 11) selected @endif>
                                На хранении
                            </option>
                            <option value="12"
                                    @if(request()->get('status') == 12) selected @endif>
                                На проверке
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <select name="material" id="material"
                                class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="all">Все материалы</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->title }}"
                                        @if($material->title == request('material')) selected @endif>{{ $material->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <select name="width" id="width" class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="all">Все ширины</option>
                            @foreach($widths as $width)
                                <option value="{{ $width->width }}"
                                        @if($width->width == request('width')) selected @endif>{{ $width->width }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <select name="height" id="height" class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="all">Все высоты</option>
                            @foreach($heights as $height)
                                <option value="{{ $height->height }}"
                                        @if($height->height == request('height')) selected @endif>{{ $height->height }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <select name="shelf" id="shelf" class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="all">Все полки</option>
                            @foreach($shelves as $shelf)
                                <option value="{{ $shelf->id }}"
                                        @if($shelf->id == request('shelf')) selected @endif>{{ $shelf->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">

            <a href="{{ route('warehouse_of_item.new_refunds') }}"
               class="btn btn-primary mr-3 mb-3">Принять новые возвраты</a>

            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="thead-dark">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Товар</th>
                        <th scope="col">Статус</th>
                        <th scope="col">Стикер</th>
                        <th scope="col">№ полки</th>
                        <th scope="col">Дата отгрузки</th>
                        <th scope="col">Дата возврата</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->item->title }}
                                - {{ $item->item->width }}
                                x {{ $item->item->height }}</td>
                            <td>
                                <span
                                    class="badge {{ $item->status_color }}"> {{ $item->status_name }}</span>
                            </td>
                            <td>
                                {{ $item->storage_barcode }}
                                <a href="{{ route('warehouse_of_item.storage_barcode', ['marketplace_item' => $item]) }}"
                                   class="btn btn-outline-secondary btn-sm ml-1"
                                   style="padding: 0px 5px;"
                                   target="_blank">
                                    <i class="fas fa-barcode"></i>
                                </a>
                            <td>
                                @if($item->shelf)
                                    {{ $item->shelf->title }}
                                @endif
                            </td>
                            <td>{{ $item->marketplaceOrder->completed_date }}</td>
                            <td>{{ $item->marketplaceOrder->returned_date }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ml-2">
                Всего: {{ $totalItems }}
            </div>

            {{-- Pagination --}}
            <x-pagination-component :collection="$items"/>

        </div>
    </div>
@stop

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
