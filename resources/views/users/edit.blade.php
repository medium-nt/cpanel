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
                            <label for="phone">Телефон</label>
                            <input type="text" class="form-control" id="phone"
                                   name="phone" value="{{ $user->phone }}">
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

                        @if($user->isStorekeeper() || $user->isOtk())
                        <div class="form-group">
                            <label for="salary_rate">Ставка (за день)</label>
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

                        <div class="form-group form-check">
                            <input type="hidden" name="is_show_finance"
                                   value="0">
                            <input type="checkbox"
                                   class="form-check-input @error('is_show_finance') is-invalid @enderror"
                                   id="is_show_finance"
                                   name="is_show_finance"
                                   value="1"
                                {{ $user->is_show_finance ? 'checked' : '' }}>
                            <label class="form-check-label"
                                   for="is_show_finance">Показ финансов</label>
                        </div>

                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="start_work_shift">Время начала смены</label>
                                    <input type="time" id="start_work_shift" name="start_work_shift"
                                           class="form-control @error('start_work_shift') is-invalid @enderror"
                                           value="{{ \Carbon\Carbon::parse($user->start_work_shift)->format('H:i') }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="duration_work_shift">Рабочее время</label>
                                    <input type="time" id="duration_work_shift"
                                           class="form-control @error('duration_work_shift') is-invalid @enderror"
                                           name="duration_work_shift" value="{{ \Carbon\Carbon::parse($user->duration_work_shift)->format('H:i') }}">
                                </div>
                                <div class="col-md-4">
                                    <label for="max_late_minutes">Время опоздания (мин.)</label>
                                    <input type="number" id="max_late_minutes"
                                           class="form-control @error('max_late_minutes') is-invalid @enderror"
                                           name="max_late_minutes" value="{{ $user->max_late_minutes }}">
                                </div>
                            </div>
                        </div>

                        @if($user->isSeamstress() || $user->isCutter())
                        <div class="form-group">
                            <label for="orders_priority">Приоритет заказов швеи или закройщика</label>
                            <select name="orders_priority" id="orders_priority" class="form-control">
                                <option value="all" {{ $user->orders_priority == 'all' ? 'selected' : '' }}>Все заказы</option>
                                <option value="fbo" {{ $user->orders_priority == 'fbo' ? 'selected' : '' }}>Только FBO</option>
                                <option value="fbo_200" {{ $user->orders_priority == 'fbo_200' ? 'selected' : '' }}>Только FBO 200 (стажер)</option>
                            </select>
                        </div>

                            @if($user->isSeamstress())
                            <div class="form-group">
                                <div class="d-flex align-items-center">
                                    <span class="mr-2">Швея (без кроя)</span>

                                    <div class="custom-control custom-switch">
                                        <input type="hidden" name="is_cutter"
                                               value="{{ $isBeforeStartWorkDay ? 0 : $user->is_cutter }}">

                                        <input type="checkbox"
                                               class="custom-control-input"
                                               name="is_cutter"
                                               value="1"
                                               id="is_cutter"
                                               @if(!$isBeforeStartWorkDay) disabled @endif
                                            @checked(old('is_cutter', $user->is_cutter))>

                                        <label class="custom-control-label" for="is_cutter"></label>
                                    </div>

                                    <span class="ml-1">Швея-закройщик</span>

                                    @if(!$isBeforeStartWorkDay)
                                        <span
                                            class="ml-3 text-danger font-weight-bold">
                                            Сегодня смена уже открывалась!
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <div class="form-group">
                                <label for="materials">Разрешенные
                                    материалы</label>
                                <select class="form-control choices"
                                        name="materials[]" multiple>
                                    @foreach($materials as $type_work)
                                        <option value="{{ $type_work->id }}"
                                            {{ in_array($type_work->id, $selectedMaterials) ? 'selected' : '' }}>
                                            {{ $type_work->title }}</option>
                                    @endforeach
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

            @if($user->isSeamstress() || $user->isCutter())
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
                                <th colspan="2" class="text-center">Бонусы за метр</th>
                            </tr>
                            <tr>
                                <th class="text-center">От</th>
                                <th class="text-center">До</th>
                                @if($user->isSeamstress())
                                <th class="text-center">с закроем</th>
                                <th class="text-center">без кроя</th>
                                @endif
                                @if($user->isCutter())
                                <th class="text-center">закройщик</th>
                                @endif
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
                                    @if($user->isSeamstress())
                                    <td>
                                        <input type="number" class="form-control"
                                               id="bonus_{{$i}}" name="bonus[]"
                                               value="{{ old('bonus')[$i] ?? $motivation?->bonus ?? '' }}">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control"
                                               id="not_cutter_bonus_{{$i}}" name="not_cutter_bonus[]"
                                               value="{{ old('not_cutter_bonus')[$i] ?? $motivation?->not_cutter_bonus ?? '' }}">
                                    </td>
                                    @endif
                                    @if($user->isCutter())
                                        <td>
                                            <input type="number" class="form-control"
                                                   id="cutter_bonus_{{$i}}" name="cutter_bonus[]"
                                                   value="{{ old('cutter_bonus')[$i] ?? $motivation?->cutter_bonus ?? '' }}">
                                        </td>
                                    @endif
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
                                <th class="text-center"></th>
                                <th colspan="2" class="text-center">Оплата</th>
                            </tr>
                            <tr>
                                <th class="text-center">Материал</th>
                                @if($user->isSeamstress())
                                <th class="text-center">с закроем</th>
                                <th class="text-center">без кроя</th>
                                @endif
                                @if($user->isCutter())
                                <th class="text-center">закройщик</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rates as $rate)
                                <tr>
                                    <td class="text-center align-middle">
                                        {{ $rate->title }}
                                    </td>
                                    @if($user->isSeamstress())
                                        <td>
                                            <input type="number"
                                                   class="form-control"
                                                   id="rate_{{$rate->id}}"
                                                   name="rate[{{$rate->id}}]"
                                                   value="{{ old('rate')[$i] ?? $rate->rates->first()->rate ?? '' }}">
                                        </td>
                                        <td>
                                            <input type="number"
                                                   class="form-control"
                                                   id="not_cutter_rate_{{$rate->id}}"
                                                   name="not_cutter_rate[{{$rate->id}}]"
                                                   value="{{ old('not_cutter_rate')[$i] ?? $rate->rates->first()->not_cutter_rate ?? '' }}">
                                        </td>
                                    @endif
                                    @if($user->isCutter())
                                        <td>
                                            <input type="number" class="form-control"
                                                   id="cutter_rate_{{$rate->id}}"
                                                   name="cutter_rate[{{$rate->id}}]"
                                                   value="{{ old('cutter_rate')[$i] ?? $rate->rates->first()->cutter_rate ?? '' }}">
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
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

@push('css')
    <link href="{{ asset('css/fullcalendar.css') }}" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <script>
        document.querySelectorAll('.choices').forEach(el => {
            new Choices(el, {
                removeItemButton: true,
                searchEnabled: true,
                shouldSort: false,
                noResultsText: 'Ничего не найдено',
                noChoicesText: 'Нет доступных вариантов',
                itemSelectText: 'Нажмите, чтобы выбрать',
                placeholderValue: 'Выберите...'
            });
        });
    </script>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="{{ asset('js/fullcalendar_by_admin.js') }}"></script>
    <script src="{{ asset('js/motivation_for_seamstress.js') }}"></script>
@endpush
