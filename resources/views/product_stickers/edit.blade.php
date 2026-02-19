@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

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

        <form
            action="{{ route('product_stickers.update', ['productSticker' => $sticker->id]) }}"
            method="POST">
            @method('PUT')
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="title">Название товара
                                            *</label>
                                        <input type="text"
                                               class="form-control @error('title') is-invalid @enderror"
                                               id="title"
                                               name="title"
                                               placeholder="Например: Тюль 280 см"
                                               value="{{ old('title', $sticker->title) }}"
                                               required>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="color">Цвет</label>
                                        <input type="text"
                                               class="form-control @error('color') is-invalid @enderror"
                                               id="color"
                                               name="color"
                                               placeholder="Например: Белый"
                                               value="{{ old('color', $sticker->color) }}">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="print_type">Вид
                                            принта</label>
                                        <input type="text"
                                               class="form-control @error('print_type') is-invalid @enderror"
                                               id="print_type"
                                               name="print_type"
                                               placeholder="Например: Цветы"
                                               value="{{ old('print_type', $sticker->print_type) }}">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="material">Материал</label>
                                        <input type="text"
                                               class="form-control @error('material') is-invalid @enderror"
                                               id="material"
                                               name="material"
                                               placeholder="Например: 100% полиэстер"
                                               value="{{ old('material', $sticker->material) }}">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="country">Страна
                                            производства</label>
                                        <input type="text"
                                               class="form-control @error('country') is-invalid @enderror"
                                               id="country"
                                               name="country"
                                               placeholder="Например: Китай"
                                               value="{{ old('country', $sticker->country) }}">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="fastening_type">Тип
                                            крепления</label>
                                        <input type="text"
                                               class="form-control @error('fastening_type') is-invalid @enderror"
                                               id="fastening_type"
                                               name="fastening_type"
                                               placeholder="Например: Петли на резинке"
                                               value="{{ old('fastening_type', $sticker->fastening_type) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    Сохранить
                                </button>
                                <a href="{{ route('product_stickers.index') }}"
                                   class="btn btn-secondary">Отмена</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@stop
