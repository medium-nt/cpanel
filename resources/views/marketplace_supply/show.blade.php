@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        @if($supply->status == 0)
            <div class="card">
                <div class="card-body">
                    @livewire('supply-order-search', ['supply' => $supply])
                </div>
            </div>
        @endif

        @livewire('supply-order-list', ['supplyId' => $supply->id])

        @if($supply->status == 0)
        <div class="card">
            <div class="card-body">
                <a href="{{ route('marketplace_supplies.complete', ['marketplace_supply' => $supply]) }}"
                   class="btn btn-primary">Закрыть поставку и передать в доставку</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Видео упаковки поставки</h3>
            </div>
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

                @if($supply->video)
                    <div class="video-container" style="margin-bottom: 20px;">
                        <video controls>
                            <source src="{{ asset('storage/videos/' . $supply->video) }}" size="1080">
                        </video>
                    </div>

                    <a href="{{ route('marketplace_supplies.delete_video', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-danger mr-3 mb-2" onclick="return confirm('Вы уверены что хотите удалить видео?')">
                        Удалить видео
                    </a>
                @else
                    <span class="text-muted">
                        разрешено загружать максимум 1 видео в формате mp4 (720p), длинной не более 2х минут и размером не более 500мб
                    </span>
                    <form method="POST" enctype="multipart/form-data"
                        action="{{ route('marketplace_supplies.download_video', ['marketplace_supply' => $supply]) }}">
                        @csrf
                        @method('PUT')
                        <div class="row mt-1">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="file" class="form-control" id="video" name="video">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Загрузить</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
        @endif

        @if($supply->status == 3)
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('marketplace_supplies.update_status_orders', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-outline-primary mb-2">Обновить статусы заказов</a>
                </div>
            </div>
        @endif

        @if($supply->status == 4)
            <div class="card">
                <div class="card-body">
                    @if($supply->marketplace_id == 1)
                    <a href="{{ route('marketplace_supplies.get_docs', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-primary mr-3 mb-2">Получить документы</a>
                    @endif

                    <a href="{{ route('marketplace_supplies.get_barcode', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-primary mr-3 mb-2">Получить штрихкод поставки</a>

                    <a href="{{ route('marketplace_supplies.done', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-success mr-3 mb-2" onclick="return confirm('Вы уверены что поставка отгружена?')">Поставка отгружена в маркетплейс</a>

                    <a href="{{ route('marketplace_supplies.update_status_orders', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-outline-primary mr-3 mb-2">Обновить статусы заказов</a>
                </div>
            </div>
        @endif
    </div>
@stop

@push('css')
    <link href="{{ asset('css/desktop_or_smartphone_card_style.css') }}" rel="stylesheet"/>

    <style>
        .video-container {
            width: 600px;
            max-width: 100%;
            aspect-ratio: 16 / 9;
        }

        .video-container video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
@endpush
