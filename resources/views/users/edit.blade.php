@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="row">
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

                <form action="{{ route('users.update', ['user' => $user->id]) }}"
                      enctype="multipart/form-data"
                      method="POST">
                    @method('PUT')
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">ФИО</label>
                            <input type="text" class="form-control" id="name"
                                   name="name" value="{{ $user->name }}" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email"
                                   name="email" value="{{ $user->email }}" required>
                        </div>

                        <div class="form-group">
                            <label for="password">новый пароль</label>
                            <input type="password" class="form-control" id="password"
                                   name="password">
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Подтверждение пароля</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                   name="password_confirmation" placeholder="Подтверждение пароля">
                        </div>

                        <div class="form-group">
                            <label for="salary_rate">Ставка (для швеи за метр, для кладовщиков за месяц)</label>
                            <input type="number" step="1" min="0"
                                   class="form-control @error('salary_rate') is-invalid @enderror"
                                   id="salary_rate"
                                   name="salary_rate"
                                   placeholder=""
                                   value="{{ $user->salary_rate }}"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="avatar">Аватар</label>
                            <div class="row">
                                <div class="col-md-11 mt-2">
                                    <input class="form-control" type="file" name="avatar" accept="image/*">
                                </div>
                                <div class="col-md-1">
                                    @if($user->avatar != null)
                                        <img src="{{ asset('storage/' . $user->avatar) }}"
                                             style="width:50px; height:50px;" alt="">
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Сохранить</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Рабочий календарь</h3>
                </div>
                <div class="card-body">
                    <div id="calendar"
                         data-events="{{ json_encode($events) }}"
                         data-csrf_token="{{ csrf_token() }}"
                         data-user_id="{{ $user->id }}"
                    ></div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('js')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="{{ asset('js/fullcalendar_by_admin.js') }}"></script>
@endpush

@push('css')
    <link href="{{ asset('css/fullcalendar.css') }}" rel="stylesheet"/>
@endpush
