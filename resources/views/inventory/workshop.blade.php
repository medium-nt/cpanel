@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        @if(auth()->user()->isAdmin())
            <a href="{{ route('movements_to_workshop.write_off') }}"
               class="btn btn-primary mr-3 mb-3">Списание материала</a>
        @endif

        @php
            $statusThresholds = function($qty) {
                if ($qty <= 100) return 'bg-danger text-white';
                if ($qty <= 300) return 'bg-warning';
                return 'bg-success text-white';
            };
            $showTotal = auth()->user()->isAdmin() || auth()->user()->isStorekeeper() || auth()->user()->isManager();
        @endphp

        @foreach ($sections as $section)
            <div class="card mb-3">
                <div
                    class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">{{ $section['title'] }}</h3>
                    <span class="badge badge-secondary">{{ count($section['items']) }} поз.</span>
                </div>
                <div class="card-body">
                    {{-- Desktop: table with colored cells --}}
                    <div class="table-responsive only-on-desktop">
                        <table
                            class="table table-hover table-bordered workshop-inventory-table">
                            <thead class="thead-dark">
                            <tr>
                                <th scope="col">Материал</th>
                                @foreach ($shifts as $shift)
                                    <th scope="col"
                                        class="text-center col-shift @if(in_array($shift->id, $todayShiftIds)) today-shift-col today-shift-col-top @endif">
                                        {{ $shift->name }}
                                        @if(in_array($shift->id, $todayShiftIds))
                                            <span
                                                class="badge badge-light ml-1">Работает</span>
                                        @endif
                                    </th>
                                @endforeach

                                @if($showTotal)
                                    <th scope="col"
                                        class="text-center col-total">Итого
                                    </th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($section['items'] as $item)
                                <tr>
                                    <td>{{ $item['material']->title }}</td>
                                    @foreach ($shifts as $shift)
                                        @php $shiftData = $item['per_shift'][$shift->id] ?? null; @endphp
                                        <td class="text-center col-shift {{ $shiftData && $shiftData['quantity'] > 0 ? $statusThresholds($shiftData['quantity']) : '' }} @if(in_array($shift->id, $todayShiftIds)) today-shift-col @if($loop->parent->last) today-shift-col-bottom @endif @endif">
                                            @if($shiftData && ($shiftData['quantity'] > 0 || $shiftData['rolls_count'] > 0))
                                                {{ $shiftData['quantity'] }} {{ $item['material']->unit }}
                                                ,
                                                {{ $shiftData['rolls_count'] }}
                                                рул.
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endforeach
                                    @if($showTotal)
                                        <td class="text-center col-total">
                                            <b>{{ $item['total_quantity'] }} {{ $item['material']->unit }}</b>,
                                            {{ $item['total_rolls'] }} рул.
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile: cards per material --}}
                    <div class="only-on-smartphone">
                        @foreach ($section['items'] as $item)
                            <div class="card mb-2">
                                <div class="card-body p-2">
                                    <b>{{ $item['material']->title }}</b>
                                    @if($showTotal)
                                        <span class="float-right">
                                            <b>{{ $item['total_quantity'] }} {{ $item['material']->unit }}</b>,
                                            {{ $item['total_rolls'] }} рул.
                                        </span>
                                    @endif
                                    <table
                                        class="table table-sm table-bordered mb-0 mt-1">
                                        @foreach ($shifts as $shift)
                                            @php $shiftData = $item['per_shift'][$shift->id] ?? null; @endphp
                                            @if($shiftData && ($shiftData['quantity'] > 0 || $shiftData['rolls_count'] > 0))
                                                <tr class="{{ $statusThresholds($shiftData['quantity']) }}">
                                                    <td class="p-1">
                                                        {{ $shift->name }}
                                                        @if(in_array($shift->id, $todayShiftIds))
                                                            <span
                                                                class="badge badge-primary ml-1">Сегодня</span>
                                                        @endif
                                                    </td>
                                                    <td class="p-1 text-right">
                                                        {{ $shiftData['quantity'] }} {{ $item['material']->unit }}
                                                        ,
                                                        {{ $shiftData['rolls_count'] }}
                                                        рул.
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@stop

@push('css')
    <link href="{{ asset('css/desktop_or_smartphone_card_style.css') }}"
          rel="stylesheet"/>
    <style>
        .today-shift-col {
            border-left: 3px solid #007bff !important;
            border-right: 3px solid #007bff !important;
        }

        .today-shift-col-top {
            border-top: 3px solid #007bff !important;
        }

        .today-shift-col-bottom {
            border-bottom: 3px solid #007bff !important;
        }

        {{-- Одинаковая ширина колонок во всех секциях: fixed-layout +
            «Материал» и «Итого» фиксированы, смены делят остаток поровну
            (число смен одинаково в каждой секции -> ширина смен совпадает) --}}
        .workshop-inventory-table {
            table-layout: fixed;
        }

        .workshop-inventory-table th,
        .workshop-inventory-table td {
            word-wrap: break-word;
        }

        .workshop-inventory-table th:first-child,
        .workshop-inventory-table td:first-child {
            width: 250px;
        }

        .workshop-inventory-table .col-total {
            width: 200px;
        }
    </style>
@endpush
