@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
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

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
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
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">SKU</h3>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ozon_sku">OZON</label>
                                        <input type="text"
                                               class="form-control @error('ozon_sku') is-invalid @enderror"
                                               id="ozon_sku"
                                               name="ozon_sku"
                                               value="{{ $item->sku()->where('marketplace_id', 1)->first()->sku ?? '' }}">
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
                                               value="{{ $item->sku()->where('marketplace_id', 2)->first()->sku ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Материалы для изделия</h3>
                        </div>

                        <div class="card-body">
                            @foreach($materialsConsumption as $materialConsumption)
                            <div class="row">
                                <div class="col-md-1">
                                    <div class="form-group">
                                        @if($loop->first)<label for="">.</label>@endif
                                        <div>
                                            <a href="{{ route('material_consumption.destroy', ['material_consumption' => $materialConsumption->id]) }}"
                                               class="btn btn-outline-danger"
                                                    onclick="return confirm('Вы уверены что хотите удалить данный материал из изделия?')">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="form-group">
                                        @if($loop->first)<label for="height">Материал</label>@endif
                                        <select name="material_id[]" id="material_id" class="form-control" required>
                                            <option value="" disabled selected>---</option>
                                            @foreach($materials as $material)
                                                <option value="{{ $material->id }}"
                                                        @if($material->id == $materialConsumption->material_id) selected @endif>
                                                    {{ $material->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        @if($loop->first)<label for="quantity">кол-во</label>@endif
                                        <input type="text"
                                               class="form-control @error('quantity') is-invalid @enderror"
                                               id="quantity"
                                               name="quantity[]"
                                               value="{{ $materialConsumption->quantity }}"
                                               required>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                            @php
                                $r = 5 - $materialsConsumption->count();
                            @endphp

                            @for($i = 0; $i < $r; $i++)
                                <x-material-component :materials="$materials"/>
                            @endfor

                            <div class="form-group">
                                <button type="submit" class="btn btn-success">Сохранить</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@stop
