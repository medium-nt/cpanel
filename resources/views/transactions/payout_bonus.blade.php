@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-6">
        <div class="card">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card-body">
                <div class="row">
                    <div class="col-md-9">
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
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <a class="btn btn-link mt-4" href="{{ route('transactions.payout_bonus') }}">Сбросить фильтр</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Бонусы на холде</h3>
            </div>
            <div class="card-body">
                <form class="mb-3" action="{{ route('transactions.store_payout_bonus') }}" method="POST"
                      onsubmit="return confirm('Вы уверены что хотите выплатить все доступные бонусы?')">
                    @method('POST')
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $selected_user->id ?? '' }}">
                    <button class="btn btn-success mr-1">Выплатить все доступные
                        бонусы
                    </button>
                    Всего: {{ $allHoldBonus }}
                </form>

                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Даты начисления</th>
                        <th>Сумма</th>
                        <th>заморожено</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($hold_bonus as $payout)
                        <tr>
                            <td>{{ $payout['accrual_for_date'] }}</td>
                            <td>{{ $payout['net_total'] }} руб.</td>
                            <td>
                                @if($payout['status'] == 1)
                                    <i class="text-success">готово к выплате</i>
                                @else
                                    <i class="text-danger">
                                        до {{ $payout['date_pay'] }}
                                    </i>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
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
