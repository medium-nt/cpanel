@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                @if(auth()->user()->role->name == 'admin')
                <div class="dropdown mb-3 mr-3">
                    <button class="btn btn-primary dropdown-toggle" type="button" id="supplyDropdown"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Добавить операцию
                    </button>
                    <div class="dropdown-menu" aria-labelledby="supplyDropdown">
                        <a class="dropdown-item" href="{{ route('transactions.create', ['type' => 'salary']) }}">зарплата</a>
                        <a class="dropdown-item" href="{{ route('transactions.create', ['type' => 'bonus']) }}">бонусы</a>
                    </div>
                </div>

                <a class="btn btn-primary mr-3 mb-3" href="{{ route('transactions.payout') }}">
                    Выплатить зарплату
                </a>
                @endif

                <form action="{{ route('transactions.index') }}" method="get" class="row g-2">
                    @if(auth()->user()->role->name == 'admin')
                    <div class="col-auto mb-3">
                        <select class="form-control" name="user_id" id="user_id">
                            <option value="0">Все</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                    @if(request('user_id') == $user->id) selected @endif>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-auto d-flex align-items-center gap-2 mb-3">
                        <input class="form-control"
                               type="date"
                               name="date_start"
                               id="date_start"
                               min="2025-08-01"
                               value="{{ request('date_start') }}">
                        <span class="mx-2"> - </span>
                        <input class="form-control"
                               type="date"
                               name="date_end"
                               id="date_end"
                               max="{{ now()->format('Y-m-d') }}"
                               value="{{ request('date_end') }}">
                    </div>

                    <div class="col-auto mb-3">
                        <button type="submit" class="btn btn-primary">Фильтр</button>
                    </div>

                    <div class="col-auto mb-3">
                        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Сбросить</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Тип</th>
                            <th scope="col">Начислено за</th>
                            <th scope="col">Деньги</th>
                            <th scope="col">Бонусы</th>
                            <th scope="col">Название</th>
                            <th scope="col">Дата создания</th>
                            <th scope="col">Дата выплаты</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td style="width: 50px">{{ $loop->iteration }}</td>
                                <td style="width: 50px">
                                    @switch($transaction->transaction_type)
                                        @case('out')
                                            <i class="fas fa-minus-circle" style="color: red"></i>
                                        @break
                                        @case('in')
                                            <i class="fas fa-plus-circle" style="color: green"></i>
                                        @break
                                    @endswitch
                                </td>
                                <td>{{ now()->parse($transaction->accrual_for_date)->format('d/m/Y') }}</td>
                                @if($transaction->is_bonus)
                                    <td></td>
                                    <td>{{ $transaction->amount }}</td>
                                @else
                                    <td>{{ $transaction->amount }}</td>
                                    <td></td>
                                @endif
                                <td>{{ $transaction->title }} @if($transaction->user_id) ({{ $transaction->user->name }}) @endif</td>
                                <td>{{ now()->parse($transaction->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{
                                    $transaction->paid_at ? \Carbon\Carbon::parse($transaction->paid_at)->format('d/m/Y H:i') : '-'
                                }}</td>

                                <td style="width: 100px">
                                    @if(auth()->user()->role->name == 'admin')
                                    <div class="btn-group" role="group">
{{--                                        <a href="{{ route('transactions.edit', ['transaction' => $transaction->id]) }}" class="btn btn-primary mr-1">--}}
{{--                                            <i class="fas fa-edit"></i>--}}
{{--                                        </a>--}}
                                        <form action="{{ route('transactions.destroy', ['transaction' => $transaction->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                    onclick="return confirm('Вы уверены что хотите удалить данную транзакцию из системы?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <x-pagination-component :collection="$transactions" />

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
    {{--    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>--}}
@endpush
