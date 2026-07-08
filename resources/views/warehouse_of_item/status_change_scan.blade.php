@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    @php
        $isUtilizeAllScenario = (int) request('from') === 16 && (int) request('to') === 19;
    @endphp

    @if($isUtilizeAllScenario && auth()->user()?->isAdmin())
        <form
            action="{{ route('warehouse_of_item.status_change_scan.utilize_defects') }}"
            method="POST">
            @csrf
            <button type="submit"
                    class="btn btn-danger ml-2"
                    onclick="return confirm('Уверены, что хотите утилизировать ВСЕ товары?')">
                <i class="fas fa-trash"></i> Утилизировать все
            </button>
        </form>
    @endif

    <livewire:status-change-scan/>
@stop
