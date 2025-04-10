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

            <form action="{{ route('marketplace_items.store') }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
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
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="width">Ширина</label>
                                <input type="number"
                                       class="form-control @error('width') is-invalid @enderror"
                                       id="width"
                                       name="width"
                                       step="100"
                                       placeholder=""
                                       value="{{ old('width') }}">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="height">Высота</label>
                                <input type="number"
                                       class="form-control @error('height') is-invalid @enderror"
                                       id="height"
                                       name="height"
                                       step="5"
                                       placeholder=""
                                       value="{{ old('height') }}">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="height">SKU:</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ozon_sku">OZON</label>
                                <input type="text"
                                       class="form-control @error('ozon_sku') is-invalid @enderror"
                                       id="ozon_sku"
                                       name="ozon_sku"
                                       value="{{ old('ozon_sku') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="wb_sku">WB</label>
                                <input type="text"
                                       class="form-control @error('wb_sku') is-invalid @enderror"
                                       id="wb_sku"
                                       name="wb_sku"
                                       value="{{ old('wb_sku') }}">
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
