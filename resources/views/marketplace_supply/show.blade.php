@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                @livewire('supply-order-search', ['supply' => $supply])

                Всего готово товаров: {{ $totalReady }}
            </div>
        </div>

        @livewire('supply-order-list', ['supplyId' => $supply->id])

    </div>
@stop

@push('css')
    <link href="{{ asset('css/desktop_or_smartphone_card_style.css') }}" rel="stylesheet"/>
@endpush
