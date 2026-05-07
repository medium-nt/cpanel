@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

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
                    @endphp

                    {{-- Desktop: table with colored cells --}}
                    <div class="table-responsive only-on-desktop">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">Материал</th>
                            @foreach ($shifts as $shift)
                                <th scope="col"
                                    class="text-center @if($shift->id === $todayShiftId) today-shift-col today-shift-col-top @endif">
                                    {{ $shift->name }}
                                    @if($shift->id === $todayShiftId)
                                        <span class="badge badge-light ml-1">Работает</span>
                                    @endif
                                </th>
                            @endforeach

                            @if(auth()->user()->isadmin() || auth()->user()->isStorekeeper())
                                <th scope="col" class="text-center">Без смены
                                </th>
                            @endif
                            @if(auth()->user()->isadmin() || auth()->user()->isStorekeeper() || auth()->user()->isManager())
                                <th scope="col" class="text-center">Итого</th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($materials as $item)
                            <tr>
                                <td>{{ $item['material']->title }}</td>
                                @foreach ($shifts as $shift)
                                    @php $shiftData = $item['per_shift'][$shift->id] ?? null; @endphp
                                    <td class="text-center {{ $shiftData && $shiftData['quantity'] > 0 ? $statusThresholds($shiftData['quantity']) : '' }} @if($shift->id === $todayShiftId) today-shift-col @if($loop->parent->last) today-shift-col-bottom @endif @endif">
                                        @if($shiftData && ($shiftData['quantity'] > 0 || $shiftData['rolls_count'] > 0))
                                            {{ $shiftData['quantity'] }} {{ $item['material']->unit }}
                                            ,
                                            {{ $shiftData['rolls_count'] }} рул.
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endforeach
                                @if(auth()->user()->isadmin() || auth()->user()->isStorekeeper())
                                    @php $noShiftData = $item['per_shift'][null] ?? null; @endphp
                                    <td class="text-center {{ $noShiftData && $noShiftData['quantity'] > 0 ? $statusThresholds($noShiftData['quantity']) : '' }}">
                                        @if($noShiftData && ($noShiftData['quantity'] > 0 || $noShiftData['rolls_count'] > 0))
                                            {{ $noShiftData['quantity'] }} {{ $item['material']->unit }}
                                            ,
                                            {{ $noShiftData['rolls_count'] }}
                                            рул.
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endif
                                @if(auth()->user()->isadmin() || auth()->user()->isStorekeeper() || auth()->user()->isManager())
                                    <td class="text-center">
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
                        @foreach ($materials as $item)
                            <div class="card mb-2">
                                <div class="card-body p-2">
                                    <b>{{ $item['material']->title }}</b>
                                    @if(auth()->user()->isadmin() || auth()->user()->isStorekeeper() || auth()->user()->isManager())
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
                                                        @if($shift->id === $todayShiftId)
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
                                            @if(auth()->user()->isAdmin() || auth()->user()->isStorekeeper())
                                                @php $noShiftData = $item['per_shift'][null] ?? null; @endphp
                                                @if($noShiftData && ($noShiftData['quantity'] > 0 || $noShiftData['rolls_count'] > 0))
                                                    <tr class="{{ $statusThresholds($noShiftData['quantity']) }}">
                                                        <td class="p-1">Без
                                                            смены
                                                        </td>
                                                        <td class="p-1 text-right">
                                                            {{ $noShiftData['quantity'] }} {{ $item['material']->unit }}
                                                            ,
                                                            {{ $noShiftData['rolls_count'] }}
                                                            рул.
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endif
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>

            </div>
        </div>
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
    </style>
@endpush
