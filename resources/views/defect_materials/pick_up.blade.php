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

            <div class="card-body">
                <div class="form-group">
                    <label for="comment">Комментарий</label>
                    <textarea class="form-control @error('comment') is-invalid @enderror"
                              id="comment"
                              rows="3"
                              minlength="3"
                              readonly>{{ $order->comment ?? old('comment') }}</textarea>
                </div>

                <div class="row">
                    @foreach($order->movementMaterials as $item)
                        <div class="col-md-10 form-group">
                            <label for="material_id">Материал</label>
                            <input type="text"
                                   class="form-control @error('material_id') is-invalid @enderror"
                                   id="material_id"
                                   value="{{ $item->material->title }}"
                                   readonly>
                        </div>

                        <div class="col-md-2 form-group">
                            <label for="quantity">Количество</label>
                            <input type="number"
                                   class="form-control @error('quantity') is-invalid @enderror"
                                   id="quantity"
                                   value="{{ $item->quantity }}"
                                   readonly>
                        </div>
                    @endforeach
                </div>

                <div class="form-group">
                    <a href="{{ route('defect_materials.save', ['order' => $order->id, 'status' => 3]) }}"
                       class="btn btn-success mr-5">Забрать брак</a>

                    <a href="{{ route('defect_materials.save', ['order' => $order->id, 'status' => -1]) }}"
                       onclick="return confirm('Вы уверены, что хотите отменить заявку?')"
                       class="btn btn-danger">Отменить заявку</a>
                </div>
            </div>
        </div>
    </div>
@stop
