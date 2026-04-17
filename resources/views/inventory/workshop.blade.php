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
                                    class="text-center">{{ $shift->name }}</th>
                            @endforeach
                            <th scope="col" class="text-center">Без смены</th>
                            <th scope="col" class="text-center">Итого</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($materials as $item)
                            <tr>
                                <td>{{ $item['material']->title }}</td>
                                @foreach ($shifts as $shift)
                                    @php $shiftData = $item['per_shift'][$shift->id] ?? null; @endphp
                                    <td class="text-center {{ $shiftData && $shiftData['quantity'] > 0 ? $statusThresholds($shiftData['quantity']) : '' }}">
                                        @if($shiftData && ($shiftData['quantity'] > 0 || $shiftData['rolls_count'] > 0))
                                            {{ $shiftData['quantity'] }} {{ $item['material']->unit }}
                                            ,
                                            {{ $shiftData['rolls_count'] }} рул.
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endforeach
                                @php $noShiftData = $item['per_shift'][null] ?? null; @endphp
                                <td class="text-center {{ $noShiftData && $noShiftData['quantity'] > 0 ? $statusThresholds($noShiftData['quantity']) : '' }}">
                                    @if($noShiftData && ($noShiftData['quantity'] > 0 || $noShiftData['rolls_count'] > 0))
                                        {{ $noShiftData['quantity'] }} {{ $item['material']->unit }}
                                        ,
                                        {{ $noShiftData['rolls_count'] }} рул.
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    <b>{{ $item['total_quantity'] }} {{ $item['material']->unit }}</b>,
                                    {{ $item['total_rolls'] }} рул.
                                </td>
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
                                    <span class="float-right">
                                    <b>{{ $item['total_quantity'] }} {{ $item['material']->unit }}</b>,
                                    {{ $item['total_rolls'] }} рул.
                                </span>
                                    <table
                                        class="table table-sm table-bordered mb-0 mt-1">
                                        @foreach ($shifts as $shift)
                                            @php $shiftData = $item['per_shift'][$shift->id] ?? null; @endphp
                                            @if($shiftData && ($shiftData['quantity'] > 0 || $shiftData['rolls_count'] > 0))
                                                <tr class="{{ $statusThresholds($shiftData['quantity']) }}">
                                                    <td class="p-1">{{ $shift->name }}</td>
                                                    <td class="p-1 text-right">
                                                        {{ $shiftData['quantity'] }} {{ $item['material']->unit }}
                                                        ,
                                                        {{ $shiftData['rolls_count'] }}
                                                        рул.
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                        @php $noShiftData = $item['per_shift'][null] ?? null; @endphp
                                        @if($noShiftData && ($noShiftData['quantity'] > 0 || $noShiftData['rolls_count'] > 0))
                                            <tr class="{{ $statusThresholds($noShiftData['quantity']) }}">
                                                <td class="p-1">Без смены</td>
                                                <td class="p-1 text-right">
                                                    {{ $noShiftData['quantity'] }} {{ $item['material']->unit }}
                                                    ,
                                                    {{ $noShiftData['rolls_count'] }}
                                                    рул.
                                                </td>
                                            </tr>
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
@endpush
