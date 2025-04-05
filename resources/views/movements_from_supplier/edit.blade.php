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

            <form action="{{ route('movements_from_supplier.update', ['order' => $order->id]) }}" method="POST">
                @method('PUT')
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="supplier_id">Поставщик</label>
                        <input type="text"
                               class="form-control"
                               id="supplier_id"
                               name="supplier_id"
                               value="{{ $order->supplier->title }}"
                               readonly>
                    </div>

                    @foreach($order->movementMaterials as $item)
                        <div class="row">
                            <div class="col-md-8 form-group">
                                <label for="material_id">Материал</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="material_id"
                                    name="material_id[]"
                                    value="{{ $item->material->title }}"
                                    readonly>
                            </div>

                            <div class="col-md-2 form-group">
                                <label for="ordered_quantity">Количество</label>
                                <input type="number"
                                       class="form-control"
                                       id="ordered_quantity"
                                       name="ordered_quantity[]"
                                       step="1"
                                       placeholder=""
                                       value="{{ $item->quantity }}"
                                       readonly>
                            </div>

                            <div class="col-md-2 form-group">
                                <label for="price">Цена</label>
                                <input type="number"
                                       class="form-control"
                                       id="price"
                                       name="price[]"
                                       step="1"
                                       value="{{ $item->price }}"
                                       required>
                            </div>

                                <input type="hidden"
                                       name="id[]"
                                       value="{{ $item->id }}">
                        </div>
                    @endforeach

                    <div class="form-group">
                        <button class="btn btn-success">Сохранить изменения</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
