@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('supply_boxes.index', ['marketplace_supply' => $supply]) }}"
                   class="btn btn-link mb-3">
                    &larr; Назад к коробам
                </a>

                <h4>{{ $box->number }}</h4>
            </div>
        </div>

        @livewire('box-order-scanner', ['box' => $box])
    </div>
@stop
