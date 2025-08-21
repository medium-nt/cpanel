@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

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

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Тип</th>
                            <th scope="col">Дата</th>
                            <th scope="col">Деньги</th>
                            <th scope="col">Бонусы</th>
                            <th scope="col">Название</th>
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
                                <td>{{ now()->parse($transaction->created_at)->format('d/m/Y H:i') }}</td>
                                @if($transaction->is_bonus)
                                    <td></td>
                                    <td>{{ $transaction->amount }}</td>
                                @else
                                    <td>{{ $transaction->amount }}</td>
                                    <td></td>
                                @endif
                                <td>{{ $transaction->title }} @if($transaction->user_id) ({{ $transaction->user->name }}) @endif</td>

                                <td style="width: 100px">
{{--                                    <div class="btn-group" role="group">--}}
{{--                                        <a href="{{ route('transactions.edit', ['transaction' => $transaction->id]) }}" class="btn btn-primary mr-1">--}}
{{--                                            <i class="fas fa-edit"></i>--}}
{{--                                        </a>--}}
{{--                                        <form action="{{ route('transactions.destroy', ['transaction' => $transaction->id]) }}" method="POST">--}}
{{--                                            @csrf--}}
{{--                                            @method('DELETE')--}}
{{--                                            <button type="submit" class="btn btn-danger"--}}
{{--                                                    onclick="return confirm('Вы уверены что хотите удалить данного поставщика из системы?')">--}}
{{--                                                <i class="fas fa-trash"></i>--}}
{{--                                            </button>--}}
{{--                                        </form>--}}
{{--                                    </div>--}}
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
