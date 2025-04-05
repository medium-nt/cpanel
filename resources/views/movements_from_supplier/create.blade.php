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

            <form action="{{ route('movements_from_supplier.store') }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="supplier_id">Поставщик</label>
                        <select name="supplier_id" id="supplier_id" class="form-control" required>
                            <option value="" disabled selected>---</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->title }}</option>
                            @endforeach
                        </select>
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
                                   step="1"
                                   value="{{ old('amount') }}"
                                   required>
                        </div>
                    </div>

                    <x-material_from_supplier-component :materials="$materials"/>
                    <x-material_from_supplier-component :materials="$materials"/>
                    <x-material_from_supplier-component :materials="$materials"/>
                    <x-material_from_supplier-component :materials="$materials"/>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Принять</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
