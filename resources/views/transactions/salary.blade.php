@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    @if(auth()->user()->role->name == 'admin')
        <div class="col-md-12">
    @else
        <div class="col-md-4">
    @endif
        <div class="card">
            <div class="card-body">

                <div class="row">
                    <div class="form-group col-md-3">
                        <input type="date"
                               name="date_start"
                               id="date_start"
                               class="form-control"
                               max="{{ now()->format('Y-m-d') }}"
                               onchange="updatePageWithQueryParam(this)"
                               value="{{ request('date_start') }}">
                    </div>

                    <div class="form-group col-md-3">
                        <input type="date"
                               name="date_end"
                               id="date_end"
                               class="form-control"
                               max="{{ now()->format('Y-m-d') }}"
                               onchange="updatePageWithQueryParam(this)"
                               value="{{ request('date_end') }}">
                    </div>

                    <div class="form-group col-md-6">
                     <a class="btn btn-link" href="{{ route('transactions.salary') }}">Сбросить фильтр</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col"></th>
                            @php
                                $salarySeamstress = [];
                            @endphp
                            @foreach($seamstresses as $seamstress)
                                <th scope="col">{{ $seamstress->name }}</th>
                                @php
                                    $salarySeamstress[$seamstress->id] = 0;
                                @endphp
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $total = [];
                        @endphp
                        @foreach($seamstressesSalary as $day => $seamstressSalary)
                            <tr>
                                <td style="width: 100px">{{ now()->parse($day)->format('d/m/Y') }}</td>

                                @foreach($seamstressSalary as $seamstressId => $salary)
                                    @php
                                        $salarySeamstress[$seamstressId] += $salary;
                                    @endphp
                                    <td>{{ $salary }}</td>
                                @endforeach
                            </tr>

                            @php
                                $total[] = $salarySeamstress[$seamstressId];
                            @endphp
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <th scope="col">Итого</th>
                            @foreach($salarySeamstress as $salary)
                                <th scope="col">{{ $salary }}</th>
                            @endforeach
                        </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>
    </div>
@stop

{{-- Push extra CSS --}}

@push('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@endpush

{{-- Push extra scripts --}}

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
