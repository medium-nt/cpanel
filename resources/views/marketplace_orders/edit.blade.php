@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-6">
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

            <form action="{{ route('marketplace_orders.update', ['marketplace_order' => $order->id]) }}" method="POST">
                @method('PUT')
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="order_id">Номер заявки</label>
                                <input type="text"
                                       class="form-control @error('order_id') is-invalid @enderror"
                                       id="order_id"
                                       name="order_id"
                                       placeholder=""
                                       value="{{ $order->order_id }}"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="marketplace_id">Маркетплейс</label>
                                <select name="marketplace_id" id="marketplace_id" class="form-control" required>
                                    <option value="" disabled selected>---</option>
                                    <option value="1" @if ($order->marketplace_id == 1) selected @endif>OZON</option>
                                    <option value="2" @if ($order->marketplace_id == 2) selected @endif>WB</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="fulfillment_type">Тип</label>
                                <select name="fulfillment_type" id="fulfillment_type" class="form-control" required>
                                    <option value="" disabled selected>---</option>
                                    <option value="FBO" @if ($order->fulfillment_type == 'FBO') selected @endif>FBO</option>
                                    <option value="FBS" @if ($order->fulfillment_type == 'FBS') selected @endif>FBS</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    @foreach($order->items as $key => $orderItem)
                    @if(!$loop->first)<hr class="d-block d-md-none">@endif
                    <div class="row">
                        <div class="col-md-9">
                            <div class="form-group">
                                <label for="item_id_{{ $key }}" @if(!$loop->first) class="d-block d-md-none" @endif>Товар</label>
                                <select name="item_id[]"
                                        id="item_id_{{ $key }}"
                                        class="form-control"
                                        @if($orderItem->status != 0) disabled @endif
                                        required>
                                    <option value="" disabled selected>---</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}"
                                                @if ($item->id == $orderItem->marketplace_item_id) selected @endif
                                        >{{ $item->title }} {{ $item->width }}х{{ $item->height }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="quantity" @if(!$loop->first) class="d-block d-md-none" @endif>Количество</label>
                                <input type="number"
                                       class="form-control @error('quantity') is-invalid @enderror"
                                       id="quantity"
                                       name="quantity[]"
                                       step="1"
                                       @if($orderItem->status != 0) readonly @endif
                                       value="{{ $orderItem->quantity }}">
                            </div>
                        </div>
                    </div>

                        <input type="hidden" name="order_item_id[]" value="{{ $orderItem->id }}">
                    @endforeach

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Сохранить</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/marketplace_orders.js') }}"></script>
@endpush

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <link href="{{ asset('css/marketplace_orders.css') }}" rel="stylesheet"/>
@endpush
