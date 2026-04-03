@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        {{-- Статистические виджеты --}}
        <div class="row mb-4">
            {{-- Готовые к осмотру --}}
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="{{ route('warehouse_of_item.new_refunds') }}"
                   class="link-black">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary">
                            <i class="fas fa-boxes"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Переданные на осмотр в цех</span>
                            <span
                                class="info-box-number">{{ $stats['returns'] }}</span>
                        </div>
                    </div>
                </a>
            </div>

            {{-- На осмотре --}}
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="{{ route('warehouse_of_item.new_refunds') }}"
                   class="link-black">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary">
                            <i class="fas fa-search"></i>
                        </span>
                        <div class="info-box-content">
                            <span
                                class="info-box-text">На осмотре у упаковщиц</span>
                            <span
                                class="info-box-number">{{ $stats['on_inspection'] }}</span>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Осмотрено --}}
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="{{ route('warehouse_of_item.status_change_scan', [
                    'from' => 15,
                    'to' => 18,
                    'title' => 'Забрать осмотренные из цеха'
                ]) }}" class="link-black">
                    <div class="info-box">
                        <span class="info-box-icon bg-success">
                            <i class="fas fa-check"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Уже осмотрено</span>
                            <span
                                class="info-box-number">{{ $stats['inspected'] }}</span>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Брак --}}
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="{{ route('warehouse_of_item.status_change_scan', [
                    'from' => 16,
                    'to' => 19,
                    'title' => 'Забрать брак из цеха'
                ]) }}" class="link-black">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Найдено с браком</span>
                            <span
                                class="info-box-number">{{ $stats['defect'] }}</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row mb-4">
            {{-- Забрано с цеха --}}
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="{{ route('warehouse_of_item.status_change_scan', [
                    'from' => 18,
                    'to' => 11,
                    'title' => 'Разместить на склад хранения'
                ]) }}" class="link-black">
                    <div class="info-box">
                        <span class="info-box-icon bg-info">
                            <i class="fas fa-dolly"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Забрано с цеха</span>
                            <span
                                class="info-box-number">{{ $stats['from_workshop'] }}</span>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Требуется утилизировать --}}
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="{{ route('warehouse_of_item.status_change_scan', [
                    'from' => 19,
                    'to' => 17,
                    'title' => 'Утилизировать'
                ]) }}" class="link-black">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning">
                            <i class="fas fa-recycle"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">На утилизацию</span>
                            <span
                                class="info-box-number">{{ $stats['to_utilize'] }}</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Кнопка печати --}}
        <div class="card">
            <div class="card-body">
                <a href="{{ route('warehouse_of_item.inspection_print') }}"
                   class="btn btn-info"
                   target="_blank">
                    <i class="fas fa-file-pdf"></i> Печать списка на осмотре
                </a>
            </div>
        </div>
    </div>
@stop
