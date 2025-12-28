@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-12">
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

            <form action="{{ route('movements_to_workshop.save_collect', ['order' => $order->id]) }}" method="POST">
                @method('PUT')
                @csrf
                <div class="card-body">
                    @foreach($order->movementMaterials as $item)
                        <div class="row">
                            <div class="col-md-8 form-group">
                                <label for="material_id">Материал</label>
                                <input type="text"
                                       class="form-control"
                                       id="material"
                                       name="material[]"
                                       value="{{ $item->material->title }}"
                                       readonly>
                            </div>

                            <div class="col-md-2 form-group">
                                <label for="roll_id">ШК-рулона</label>
                                <input type="text"
                                       class="form-control @error('roll_code.' . $loop->index) is-invalid @enderror"
                                       value="{{ old('roll_code.' . $loop->index, $item->roll_code) }}"
                                       name="roll_code[]"
                                       id="roll_code"
                                       autofocus>
                            </div>

                            <input type="hidden" name="id[]" value="{{ $item->id }}">
                            <input type="hidden" name="material_id[]"
                                   value="{{ $item->material->id }}">
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
                        <button class="btn btn-success">Подтвердить отгрузку
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
