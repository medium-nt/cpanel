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

            <form action="{{ route('movements_to_workshop.save_receive', ['order' => $order->id]) }}" method="POST">
                @method('PUT')
                @csrf
                <div class="card-body">
                    @foreach($order->movementMaterials as $item)
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label for="material_id">Материал</label>
                            <input type="text"
                                   class="form-control"
                                   id="material_id"
                                   name="material_id[]"
                                   value="{{ $item->material->title }}"
                                   readonly>
                        </div>

                        <div class="col-md-2 form-group">
                            <label for="ordered_quantity">Заказано</label>
                            <input type="number"
                                   class="form-control"
                                   id="ordered_quantity"
                                   value="{{ $item->ordered_quantity }}"
                                   readonly>
                        </div>

                        <div class="col-md-2 form-group">
                            <label for="quantity">Отгружено</label>
                            <input type="number"
                                   class="form-control"
                                   id="quantity"
                                   name="quantity[]"
                                   step="1"
                                   min="0"
                                   value="{{ $item->quantity }}"
                                   readonly>
                        </div>

                        <input type="hidden" name="id[]" value="{{ $item->id }}">
                    </div>
                    @endforeach

                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label for="comment">Комментарий</label>
                                <textarea rows="3"
                                          class="form-control"
                                          readonly>{{ $order->comment }}</textarea>
                            </div>
                        </div>

                    <div class="form-group">
                        <button class="btn btn-success">Принять поставку</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
