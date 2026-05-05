@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-12">
        @livewire('workshop-roll-scan', ['order' => $order])
    </div>
@stop
