@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-6">
        <div class="card">

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('transactions.store_payout_salary') }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Сотрудник</label>
                        <select
                            name="user_id"
                            id="user_id"
                            class="form-control"
                            onchange="updatePageWithQueryParam(this)"
                            required
                        >
                            <option value="" disabled selected>---</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id', $selected_user?->id) == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>

                        @isset($selected_user)
                            @if ($oldestUnpaidSalaryDate)
                                <span class="text-danger ml-1">
                                    первое неоплаченное начисление: {{ Carbon\Carbon::parse($oldestUnpaidSalaryDate)->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-success ml-1">
                                    все начисления выплачены
                                </span>
                            @endif
                        @endisset
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date">Начало периода</label>
                                <input type="date"
                                       id="start_date"
                                       name="start_date"
                                       max="{{ date('Y-m-d') }}"
                                       class="form-control @error('start_date') is-invalid @enderror"
                                       value="{{ old('start_date', $request->input('start_date')) }}"
                                       onchange="updatePageWithQueryParam(this)"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date">Конец периода</label>
                                <input type="date"
                                       id="end_date"
                                       name="end_date"
                                       max="{{ date('Y-m-d') }}"
                                       class="form-control @error('end_date') is-invalid @enderror"
                                       value="{{ old('end_date', $request->input('end_date')) }}"
                                       onchange="updatePageWithQueryParam(this)"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <a class="btn btn-link mt-4" href="{{ route('transactions.payout_salary') }}">Сбросить фильтр</a>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary mr-2"
                                onclick="return confirm('Вы передали сумму {{ $net_payout }} руб. сотруднику?')">
                            Выплатить
                        </button>
                        <span>К выплате: <b>{{ $net_payout }} руб.</b></span>
                    </div>

                    @if ($moneyInCompany < $net_payout)
                        <div class="form-group">
                            <span class="alert alert-danger d-block"
                                  style="background-color: #f8d7da; color: #721c24; border-radius: 0; padding: 10px 15px;">
                                <b>В кассе не хватает денег для такой выплаты</b>
                            </span>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Последние выплаты</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Дата выплаты</th>
                        <th>Сумма</th>
                        <th>Даты начисления</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($payouts as $payout)
                        <tr @if($payout['payout_date'] == Carbon\Carbon::now()->format('d/m/Y')) style="font-weight: bold; color: darkgreen" @endif>
                            <td>{{ $payout['payout_date'] }}</td>
                            <td>{{ $payout['net_total'] }} руб.</td>
                            <td>
                                {{ Carbon\Carbon::parse($payout['accrual_range']['from'])->format('d/m/Y') }}
                                -
                                {{ Carbon\Carbon::parse($payout['accrual_range']['to'])->format('d/m/Y') }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
