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

            <form action="{{ route('defect_materials.store') }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">

                    <div class="form-group">
                        <label for="comment">Комментарий</label>
                        <textarea class="form-control @error('comment') is-invalid @enderror"
                                  id="comment"
                                  name="comment"
                                  rows="3"
                                  minlength="3"
                                  value="{{ old('comment') }}"
                                  required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-10 form-group">
                            <label for="material_id">Материал</label>
                            <select name="material_id[]" id="material_id" class="form-control" required>
                                <option value="" disabled selected>---</option>
                                @foreach($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 form-group">
                            <label for="quantity">Количество</label>
                            <input type="number"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   id="quantity"
                                   name="quantity[]"
                                   value="{{ old('amount') }}"
                                   step="0.01"
                                   min="0.01"
                                   required>
                        </div>
                    </div>

                    <x-material-component :materials="$materials"/>
                    <x-material-component :materials="$materials"/>
                    <x-material-component :materials="$materials"/>
                    <x-material-component :materials="$materials"/>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Принять</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
