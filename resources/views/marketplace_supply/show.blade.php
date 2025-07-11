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
                <a href="{{ route('marketplace_supplies.complete', ['marketplace_supply' => $supply]) }}" class="btn btn-primary">Закрыть поставку и передать в доставку</a>
            </div>
        </div>
        @endif

        @if($supply->status == 3)
            <div class="card">
                <div class="card-body">
                    @if($supply->marketplace_id == 1)
                    <a href="{{ route('marketplace_supplies.get_docs', ['marketplace_supply' => $supply]) }}" class="btn btn-primary mr-3 mb-2">Получить документы</a>
                    @endif
                    <a href="{{ route('marketplace_supplies.get_barcode', ['marketplace_supply' => $supply]) }}" class="btn btn-primary mr-3 mb-2">Получить штрихкод поставки</a>
                    <a href="{{ route('marketplace_supplies.update_status_orders', ['marketplace_supply' => $supply]) }}" class="btn btn-outline-primary mb-2">Обновить статусы заказов</a>
                </div>
            </div>
        @endif
    </div>
@stop

@push('css')
    <link href="{{ asset('css/desktop_or_smartphone_card_style.css') }}" rel="stylesheet"/>
@endpush
