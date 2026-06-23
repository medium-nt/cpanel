@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <a href="{{ route('warehouse_of_item.to_pick_list') }}"
       class="btn btn-outline-secondary mb-3 ml-2">
        <i class="fas fa-arrow-left mr-1"></i> Назад
    </a>

    <livewire:pickup-scan/>
@stop
