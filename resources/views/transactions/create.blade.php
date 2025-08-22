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

            <form action="{{ route('transactions.store', ['type' => $type]) }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Название</label>
                        <input type="text"
                               id="title"
                               name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}"
                               required>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="amount">Сумма</label>
                                <input type="number"
                                       class="form-control @error('amount') is-invalid @enderror"
                                       id="amount"
                                       name="amount"
                                       min="0"
                                       step="0.01"
                                       value="{{ old('amount') }}"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="transaction_type">Тип</label>
                                <select
                                    name="transaction_type"
                                    id="transaction_type"
                                    class="form-control"
                                    required
                                >
                                    <option value="" disabled selected>---</option>
                                    <option value="in" @selected(old('transaction_type') == 'in')>Поступление</option>
                                    <option value="out" @selected(old('transaction_type') == 'out')>Списание</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date">Дата начисления</label>
                                <input type="date"
                                       id="accrual_for_date"
                                       name="accrual_for_date"
                                       min="{{ date('Y-m-01') }}"
                                       max="{{ date('Y-m-d') }}"
                                       class="form-control @error('accrual_for_date') is-invalid @enderror"
                                       value="{{ old('accrual_for_date', date('Y-m-d')) }}"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="user_id">Сотрудник</label>
                        <select
                            name="user_id"
                            id="user_id"
                            class="form-control"
                            required
                        >
                            <option value="" selected disabled>---</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Создать</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
