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

            <form action="{{ route('marketplace_items.update', ['marketplace_item' => $item->id]) }}" method="POST">
                @method('PUT')
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
                                       value="{{ $item->title }}"
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
                                       value="{{ $item->width }}">
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
                                       value="{{ $item->height }}">
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
                                       value="{{ $item->sku()->where('marketplace_id', 1)->first()->sku }}">
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
                                       value="{{ $item->sku()->where('marketplace_id', 2)->first()->sku }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Сохранить</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
