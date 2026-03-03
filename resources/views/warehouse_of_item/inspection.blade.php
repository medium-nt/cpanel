@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        {{-- Статистические виджеты --}}
        <div class="row mb-4">
            {{-- На осмотре --}}
            <div class="col-md-4">
                <div class="card bg-secondary">
                    <div class="card-body">
                        <h3 class="card-title text-white">{{ $stats['on_inspection'] }}</h3>
                        <p class="card-text text-white mb-0">На осмотре</p>
                    </div>
                </div>
            </div>

            {{-- Осмотрено --}}
            <div class="col-md-4">
                <div class="card bg-success">
                    <div class="card-body">
                        <h3 class="card-title text-white">{{ $stats['inspected'] }}</h3>
                        <p class="card-text text-white mb-0">Осмотрено</p>
                    </div>
                </div>
            </div>

            {{-- Брак --}}
            <div class="col-md-4">
                <div class="card bg-danger">
                    <div class="card-body">
                        <h3 class="card-title text-white">{{ $stats['defect'] }}</h3>
                        <p class="card-text text-white mb-0">Брак</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Выбор типа стикера --}}
        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('warehouse_of_item.set_sticker_type') }}"
                      method="post" class="form-inline">
                    @csrf
                    <label class="mr-2">Тип стикера:</label>
                    <select name="sticker_type" class="form-control mr-2"
                            onchange="this.form.submit()">
                        <option value="FBO"
                                @if($stickerType == 'FBO') selected @endif>FBO
                        </option>
                        <option value="storage"
                                @if($stickerType == 'storage') selected @endif>
                            Хранение
                        </option>
                    </select>
                </form>
            </div>
        </div>

        {{-- Кнопки действий --}}
        <div class="card">
            <div class="card-body">
                <a href="{{ route('warehouse_of_item.new_refunds') }}"
                   class="btn btn-primary btn-lg mr-3 mb-3">
                    <i class="fas fa-search"></i> Отправить на осмотр
                </a>

                <button class="btn btn-success btn-lg mb-3 mr-3" disabled>
                    <i class="fas fa-check"></i> Принять осмотренные
                </button>

                <button class="btn btn-danger btn-lg mb-3" disabled>
                    <i class="fas fa-trash"></i> Принять брак
                </button>
            </div>
        </div>
    </div>
@stop
