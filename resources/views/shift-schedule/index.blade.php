@extends('layouts.app')

@section('subtitle', 'Календарь смен')
@section('content_header_title', 'Календарь смен')

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <label for="workshop_filter"
                           class="font-weight-bold mb-0 mr-1">
                        <i class="fas fa-industry mr-1"></i> Цех:
                    </label>
                    <select id="workshop_filter" class="form-control"
                            style="width: auto; min-width: 200px;"
                            onchange="window.location.href = '{{ route('shift-schedule.index') }}?month={{ $currentDate->month }}&year={{ $currentDate->year }}&workshop_id=' + this.value">
                        @foreach ($workshops as $workshop)
                            <option value="{{ $workshop->id }}"
                                {{ $workshopId == $workshop->id ? 'selected' : '' }}>
                                {{ $workshop->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('shift-schedule.index', ['month' => $prevMonth->month, 'year' => $prevMonth->year, 'workshop_id' => $workshopId]) }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <h3 class="card-title mb-0">
                        {{ ucfirst($currentDate->locale('ru')->monthName) }} {{ $currentDate->year }}
                    </h3>
                    <a href="{{ route('shift-schedule.index', ['month' => $nextMonth->month, 'year' => $nextMonth->year, 'workshop_id' => $workshopId]) }}"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('shift-schedule.store') }}" method="POST"
                      id="scheduleForm">
                    @csrf
                    <input type="hidden" name="month"
                           value="{{ $currentDate->month }}">
                    <input type="hidden" name="year"
                           value="{{ $currentDate->year }}">
                    <input type="hidden" name="workshop_id"
                           value="{{ $workshopId }}">

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-dark">
                            <tr>
                                <th>Пн</th>
                                <th>Вт</th>
                                <th>Ср</th>
                                <th>Чт</th>
                                <th>Пт</th>
                                <th>Сб</th>
                                <th>Вс</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $startOfMonth = $currentDate->copy()->startOfMonth();
                                $endOfMonth = $currentDate->copy()->endOfMonth();
                                $startOfCalendar = $startOfMonth->copy()->startOfWeek();
                                $endOfCalendar = $endOfMonth->copy()->endOfWeek();
                                $existingSchedule = \App\Models\ShiftSchedule::query()
                                    ->whereIn('shift_id', $shifts->pluck('id'))
                                    ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                                    ->pluck('shift_id', 'date');
                                $today = \Carbon\Carbon::today()->toDateString();
                            @endphp

                            @while($startOfCalendar <= $endOfCalendar)
                                <tr>
                                    @for($i = 0; $i < 7; $i++)
                                        @php
                                            $date = $startOfCalendar->copy();
                                            $isCurrentMonth = $date->month === $currentDate->month;
                                            $isPast = $date->toDateString() <= $today;
                                            $isToday = $date->toDateString() === $today;
                                            $selectedShift = $existingSchedule[$date->toDateString()] ?? null;
                                        @endphp
                                        <td class="{{ !$isCurrentMonth ? 'text-muted' : '' }} {{ $isPast && $isCurrentMonth ? 'bg-secondary-light' : '' }}"
                                            style="min-width: 120px; {{ $isToday ? 'border: 2px solid #007bff !important;' : '' }}">
                                            <div
                                                class="small font-weight-bold">{{ $date->format('d.m') }}</div>
                                            @if($isCurrentMonth)
                                                @if($isPast)
                                                    {{-- Прошедшие даты — readonly --}}
                                                    <select
                                                        class="form-control form-control-sm"
                                                        disabled>
                                                        @if($selectedShift)
                                                            @foreach ($shifts as $shift)
                                                                @if($shift->id == $selectedShift)
                                                                    <option
                                                                        selected>{{ $shift->name }}</option>
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            <option>--</option>
                                                        @endif
                                                    </select>
                                                @else
                                                    <input type="hidden"
                                                           name="dates[{{ $date->toDateString() }}][date]"
                                                           value="{{ $date->toDateString() }}">
                                                    <select
                                                        name="dates[{{ $date->toDateString() }}][shift_id]"
                                                        class="form-control form-control-sm schedule-select"
                                                        data-date="{{ $date->toDateString() }}">
                                                        <option value="">--
                                                        </option>
                                                        @foreach ($shifts as $shift)
                                                            <option
                                                                value="{{ $shift->id }}" {{ $selectedShift == $shift->id ? 'selected' : '' }}>
                                                                {{ $shift->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            @endif
                                        </td>
                                        @php $startOfCalendar->addDay(); @endphp
                                    @endfor
                                </tr>
                            @endwhile
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить календарь
                    </button>
                    <a href="{{ route('shifts.index') }}"
                       class="btn btn-secondary">Назад к сменам</a>
                </form>
            </div>
        </div>
    </div>
@stop

@push('js')
@endpush
