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

        {{-- Кнопки действий --}}
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center">
                <a href="{{ route('warehouse_of_item.new_refunds') }}"
                   class="btn btn-primary btn mr-3 mb-3">
                    <i class="fas fa-search"></i> Отправить на осмотр
                </a>

                <a href="{{ route('warehouse_of_item.inspection_print') }}"
                   class="btn btn-info btn mr-3 mb-3"
                   target="_blank">
                    <i class="fas fa-file-pdf"></i> Печать списка на осмотре
                </a>

                <a href="{{ route('warehouse_of_item.status_change_scan', [
                    'from' => 15,
                    'to' => 18,
                    'title' => 'Принять осмотренные'
                ]) }}"
                   class="btn btn-success mr-3 mb-3">
                    <i class="fas fa-check"></i> Принять осмотренные
                </a>

                <a href="{{ route('warehouse_of_item.status_change_scan', [
                    'from' => 16,
                    'to' => 19,
                    'title' => 'Принять брак'
                ]) }}"
                   class="btn btn-danger mb-3">
                    <i class="fas fa-trash"></i> Принять брак
                </a>
            </div>
        </div>
    </div>
@stop
