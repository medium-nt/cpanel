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

            <form action="{{ route('users.store') }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Имя</label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               placeholder=""
                               value="{{ old('name') }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               id="email"
                               name="email"
                               placeholder=""
                               value="{{ old('email') }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="text"
                               class="form-control @error('phone') is-invalid @enderror"
                               id="phone"
                               name="phone"
                               placeholder=""
                               value="{{ old('phone') }}">
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               id="password"
                               name="password"
                               placeholder=""
                               value="{{ old('password') }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Подтверждение пароля</label>
                        <input type="password"
                               class="form-control @error('password_confirmation') is-invalid @enderror"
                               id="password_confirmation"
                               name="password_confirmation"
                               placeholder=""
                               value="{{ old('password_confirmation') }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="role_id">Роль</label>
                        <select name="role_id" id="role_id" class="form-control" required>
                            <option value="" disabled selected>---</option>
                            <option value="1"
                                    @if(old('role_id') == 1) selected @endif>
                                Швея
                            </option>
                            <option value="2"
                                    @if(old('role_id') == 2) selected @endif>
                                Кладовщик
                            </option>
                            <option value="4"
                                    @if(old('role_id') == 4) selected @endif>
                                Закройщик
                            </option>
                            <option value="5"
                                    @if(old('role_id') == 5) selected @endif>
                                Сотрудник ОТК
                            </option>
                            <option value="6"
                                    @if(old('role_id') == 6) selected @endif>
                                Водитель
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="salary_rate">Ставка (для швеи за метр, для
                            кладовщиков, ОТК и водителей - за день)</label>
                        <input type="text"
                               class="form-control @error('salary_rate') is-invalid @enderror"
                               id="salary_rate"
                               name="salary_rate"
                               placeholder=""
                               value="{{ old('salary_rate') }}"
                               required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
