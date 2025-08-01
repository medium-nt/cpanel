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

            <form action="{{ route('profile.update') }}" method="POST">
                @method('PUT')
                @csrf
                <div class="card-body">

                    @if($user->tg_id == '')
                        <div class="alert alert-danger" role="alert">
                            <h4 class="alert-heading">Telegram не подключен</h4>
                            Для получения уведомлений через Telegram необходимо
                            <a href="{{ config('telegram.bots.mybot.link') }}" target="_blank">подключить бота</a>
                            <br>
                            <a href="{{ route('profile') }}">(проверить подключение)</a>
                        </div>
                    @else
                        <div class="alert alert-success" role="alert">
                            <h4 class="alert-heading">Telegram подключен</h4>
                            Для отключения уведомлений через Telegram <a href="{{ route('profile.disconnectTg') }}">нажмите тут</a>
                        </div>
                    @endif


                    <div class="form-group">
                        <label for="name">Имя</label>
                        <input type="text" class="form-control" id="name" value="{{ $user->name }}"
                               name="name" placeholder="Имя" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" value="{{ $user->email }}"
                               name="email" placeholder="Email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Новый пароль</label>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Пароль">
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Подтверждение пароля</label>
                        <input type="password" class="form-control" id="password_confirmation"
                               name="password_confirmation" placeholder="Подтверждение пароля">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </div>
            </form>
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
