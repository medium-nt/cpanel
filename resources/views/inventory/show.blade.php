@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <livewire:inventory-check-scan :inventory-check="$inventory"/>
@stop
