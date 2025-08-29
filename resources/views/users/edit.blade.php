@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="row">
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

                        @if($user->role->name == 'storekeeper')
                        <div class="form-group">
                            <label for="salary_rate">Ставка кладовщика (за день)</label>
                            <input type="number" step="1" min="0"
                                   class="form-control @error('salary_rate') is-invalid @enderror"
                                   id="salary_rate"
                                   name="salary_rate"
                                   placeholder=""
                                   value="{{ $user->salary_rate }}"
                                   required>
                        </div>
                        @endif

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

                        @if($user->role->name == 'seamstress')
                        <div class="form-group">
                            <label for="orders_priority">Приоритет заказов швеи</label>
                            <select name="orders_priority" id="orders_priority" class="form-control">
                                <option value="all" {{ $user->orders_priority == 'all' ? 'selected' : '' }}>Все заказы</option>
                                <option value="fbo" {{ $user->orders_priority == 'fbo' ? 'selected' : '' }}>Только FBO</option>
                                <option value="fbo_200" {{ $user->orders_priority == 'fbo_200' ? 'selected' : '' }}>Только FBO 200 (стажер)</option>
                            </select>
                        </div>
                        @endif

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

            @if($user->role->name == 'seamstress')
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Таблица мотивации</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.motivation_update', ['user' => $user->id]) }}"
                          method="POST">
                        @method('PUT')
                        @csrf
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th colspan="2" class="text-center">Объем в день (метров)</th>
                                <th colspan="2" class="text-center">Ставка за метр</th>
                            </tr>
                            <tr>
                                <th class="text-center">От</th>
                                <th class="text-center">До</th>
                                <th class="text-center">Бонус</th>
                            </tr>
                            </thead>
                            <tbody>
                            @for($i = 0; $i < 6; $i++)
                                @php
                                    $motivation = $motivations[$i] ?? null;
                                @endphp

                                <tr>
                                    <td>
                                        <input type="number" class="form-control"
                                               id="from_{{$i}}" name="from[]"
                                               value="@if($i == 0){{ 0 }}@else{{ old('from')[$i] ?? $motivation?->from ?? $previous_to ?? '' }}@endif"
                                               readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control"
                                               id="to_{{$i}}" name="to[]"
                                               value="{{ old('to')[$i] ?? $motivation?->to ?? '' }}">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control"
                                               id="bonus_{{$i}}" name="bonus[]"
                                               value="{{ old('bonus')[$i] ?? $motivation?->bonus ?? '' }}">
                                    </td>
                                </tr>
                                @php
                                    $previous_to = old('to')[$i] ?? $motivation?->to ?? '';
                                @endphp
                            @endfor
                            </tbody>
                        </table>

                        <button type="submit" class="btn btn-primary" id="saveMotivation">Сохранить</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Таблица зарплаты</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.rate_update', ['user' => $user->id]) }}"
                          method="POST">
                        @method('PUT')
                        @csrf
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th class="text-center">Ширина</th>
                                <th class="text-center">Оплата</th>
                            </tr>
                            </thead>
                            <tbody>
                            @for($i = 0; $i < 7; $i++)
                                @php
                                    $rate = $rates[$i] ?? null;
                                    $width = ($i + 2) * 100;
                                @endphp

                                <tr>
                                    <td>
                                        <input type="number" class="form-control"
                                               id="width_{{$i}}" name="width[]"
                                               value="{{ old('width')[$i] ?? $rate?->width ?? $width }}"
                                               readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control"
                                               id="rate_{{$i}}" name="rate[]"
                                               value="{{ old('rate')[$i] ?? $rate?->rate ?? '' }}">
                                    </td>
                                </tr>
                            @endfor
                            </tbody>
                        </table>

                        <button type="submit" class="btn btn-primary" id="saveMotivation">Сохранить</button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
@stop

@push('js')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="{{ asset('js/fullcalendar_by_admin.js') }}"></script>
    <script src="{{ asset('js/motivation_for_seamstress.js') }}"></script>
@endpush

@push('css')
    <link href="{{ asset('css/fullcalendar.css') }}" rel="stylesheet"/>
@endpush
