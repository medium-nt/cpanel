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

            <form action="{{ route('movements_to_workshop.store') }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-10 form-group">
                                <label for="material_id">Материал</label>
                                <select id="material_id"
                                        name="material_id" class="form-control" required>
                                    <option value="" disabled selected>---</option>
                                    @foreach($materials as $material)
                                        <option value="{{ $material->id }}"
                                                @if($movement->material_id == $material->id) selected @endif
                                        >{{ $material->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 form-group">
                                <label for="ordered_quantity">Количество</label>
                                <input type="number"
                                       class="form-control @error('orderedQuantity') is-invalid @enderror"
                                       id="ordered_quantity"
                                       name="ordered_quantity"
                                       step="1"
                                       placeholder=""
                                       value="{{ old('orderedQuantity') ?? 0 }}"
                                required>
                            </div>
                        </div>

                        <div class="form-group">
                            <button class="btn btn-primary">Оформить заказ</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
