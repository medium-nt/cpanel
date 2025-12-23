@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">

        <div class="card">
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $isSaved = request()->has('is_saved') ? request('is_saved') : 0;
                @endphp

                <form action="{{ route('warehouse_of_item.save_group') }}"
                      method="get">
                    <div class="row">
                        <div class="col-md-1 mb-3">
                            <a href="{{ route('warehouse_of_item.index') }}"
                               class="btn btn-outline-secondary w-100">
                                <i class="fas fa-arrow-left mr-2"></i>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <select name="material_title" id="material_title"
                                    class="form-control"
                                    @if($isSaved == 1) disabled @endif required>
                                <option value="" disabled selected>Выберите
                                    материал
                                </option>
                                @foreach($materials as $material)
                                    <option value="{{ $material->title }}"
                                            @if($material->title == old('material_title', request('material_title'))) selected @endif>
                                        {{ $material->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <select name="width" id="width" class="form-control"
                                    @if($isSaved == 1) disabled @endif required>
                                <option value="" disabled selected>Выберите
                                    ширину
                                </option>
                                @foreach($widths as $width)
                                    <option value="{{ $width->width }}"
                                            @if($width->width == old('width', request('width'))) selected @endif>
                                        {{ $width->width }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <select name="height" id="height"
                                    class="form-control"
                                    @if($isSaved == 1) disabled @endif required>
                                <option value="" disabled selected>Выберите
                                    высоту
                                </option>
                                @foreach($heights as $height)
                                    <option value="{{ $height->height }}"
                                            @if($height->height == old('height', request('height'))) selected @endif>
                                        {{ $height->height }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <select name="shelf_id" id="shelf_id"
                                    class="form-control"
                                    @if($isSaved == 1) disabled @endif required>
                                <option value="" disabled selected>Выберите
                                    полку
                                </option>
                                @foreach($shelves as $shelf)
                                    <option value="{{ $shelf->id }}"
                                            @if($shelf->id == old('shelf_id', request('shelf_id'))) selected @endif>
                                        {{ $shelf->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <input type="number" class="form-control"
                                   name="quantity"
                                   min="1" placeholder="Количество"
                                   value="{{ old('quantity', request('quantity')) }}"
                                   @if($isSaved == 1) disabled @endif required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <select name="seamstress_id" id="seamstress_id"
                                    class="form-control"
                                    @if($isSaved == 1) disabled @endif required>
                                <option value="" disabled selected>Выберите
                                    швею
                                </option>
                                @foreach($seamstresses as $seamstress)
                                    <option value="{{ $seamstress->id }}"
                                            @if($seamstress->id == old('seamstress_id', request('seamstress'))) selected @endif>
                                        {{ $seamstress->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <select name="cutter_id" id="cutter_id"
                                    class="form-control"
                                    @if($isSaved == 1) disabled @endif required>
                                <option value="" disabled selected>Выберите
                                    закройщика
                                </option>
                                <option value="">Без закройщика</option>
                                @foreach($cutters as $cutter)
                                    <option value="{{ $cutter->id }}"
                                            @if($cutter->id == old('cutter_id', request('cutter'))) selected @endif>
                                        {{ $cutter->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($isSaved)
                        <script>
                            let stickersPrinted = false;
                            document.addEventListener('DOMContentLoaded', function () {
                                // Блокируем все ссылки
                                document.querySelectorAll('a').forEach(link => {
                                    link.addEventListener('click', function (e) {
                                        if (!stickersPrinted && !link.classList.contains('print-link')) {
                                            e.preventDefault();
                                            alert('Вы не распечатали стикеры!');
                                        }
                                    });
                                });

                                document.querySelector('.print-link').addEventListener('click', function (e) {
                                    this.classList.remove('btn-success');
                                    this.classList.add('btn-outline-secondary');

                                    stickersPrinted = true;
                                });
                            });
                        </script>

                        <div class="row">
                            <div class="col-md-12">
                                <a class="btn btn-success print-link"
                                   target="_blank"
                                   href="{{ route('warehouse_of_item.storage_barcode', ['marketplace_items' => request('marketplace_items')]) }}">
                                    Распечатать стикеры</a>
                            </div>
                        </div>
                    @else
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    Сохранить товары
                                </button>
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
@stop
