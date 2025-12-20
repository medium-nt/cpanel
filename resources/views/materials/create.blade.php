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

            <form action="{{ route('materials.store') }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Название</label>
                        <input type="text"
                               class="form-control @error('title') is-invalid @enderror"
                               id="title"
                               name="title"
                               placeholder=""
                               value="{{ old('title') }}"
                               required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type_id">Тип</label>
                                <select name="type_id" id="type_id" class="form-control" required>
                                    <option value="" disabled selected>---</option>
                                    @foreach($typesMaterial as $typeMaterial)
                                        <option value="{{$typeMaterial->id}}">{{$typeMaterial->title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="unit">Ед. измерения</label>
                                <input type="text"
                                       class="form-control @error('unit') is-invalid @enderror"
                                       id="unit"
                                       name="unit"
                                       maxlength="10"
                                       placeholder=""
                                       value="{{ old('unit') }}"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label
                                    for="purchase_price">Себестоимость</label>
                                <input type="number"
                                       class="form-control @error('purchase_price') is-invalid @enderror"
                                       id="purchase_price"
                                       name="purchase_price"
                                       min="0.01"
                                       step="0.01"
                                       placeholder="за единицу"
                                       value="{{ old('purchase_price') }}"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Создать</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
