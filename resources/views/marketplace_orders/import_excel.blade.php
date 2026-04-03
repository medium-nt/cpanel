@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12 col-lg-12">
        <livewire:excel-order-import/>
    </div>
@stop
