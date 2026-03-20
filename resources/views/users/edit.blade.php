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

                        @if($user->isStorekeeper() || $user->isOtk() || $user->isDriver())
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
            @endif

            @if($user->isSeamstress() || $user->isCutter() || $user->isOtk())
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
                                @if($user->isOtk())
                                    <th class="text-center">упаковка</th>
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
                                    @if($user->isOtk())
                                        <td>
                                            <input type="number"
                                                   class="form-control"
                                                   id="rate_{{$rate->id}}"
                                                   name="rate[{{$rate->id}}]"
                                                   value="{{ $rate->rates->first()->rate ?? '' }}">
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

    {{-- Новая система тарифов --}}
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Тарифы</h3>
                </div>
                <div class="card-body">
                    <form
                        action="{{ route('users.tariffs_update', ['user' => $user->id]) }}"
                        method="POST" id="tariffsForm">
                        @method('PUT')
                        @csrf

                        {{-- ============================================= --}}
                        {{-- ОКЛАД --}}
                        {{-- ============================================= --}}
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title">Оклад</h5>
                            </div>
                            <div class="card-body">
                                {{-- Зарплата --}}
                                <div class="card mb-2">
                                    <div class="card-header p-2 bg-light"
                                         data-bs-toggle="collapse"
                                         data-bs-target="#salary-oklad"
                                         style="cursor: pointer;">
                                        <h6 class="mb-0 d-flex justify-content-between align-items-center">
                                            <span>Зарплата</span>
                                            <i class="fas fa-chevron-right"></i>
                                        </h6>
                                    </div>
                                    <div id="salary-oklad" class="collapse">
                                        <div class="card-body">
                                            <input type="number"
                                                   class="form-control"
                                                   name="salary[fixed_salary_per_day]"
                                                   placeholder="0"
                                                   min="0"
                                                   step="0.01"
                                                   value="{{ $userTariffsSalary->get('Оклад')?->tariffs->first()?->value ?? old('salary.fixed_salary_per_day') }}">
                                        </div>
                                    </div>
                                </div>

                                {{-- Бонусы --}}
                                <div class="card mb-2">
                                    <div class="card-header p-2 bg-light"
                                         data-bs-toggle="collapse"
                                         data-bs-target="#bonus-oklad"
                                         style="cursor: pointer;">
                                        <h6 class="mb-0 d-flex justify-content-between align-items-center">
                                            <span>Бонусы</span>
                                            <i class="fas fa-chevron-right"></i>
                                        </h6>
                                    </div>
                                    <div id="bonus-oklad" class="collapse">
                                        <div class="card-body">
                                            <input type="number"
                                                   class="form-control"
                                                   name="bonus[fixed_salary_per_day]"
                                                   placeholder="0"
                                                   min="0"
                                                   step="0.01"
                                                   value="{{ $userTariffsBonus->get('Оклад')?->tariffs->first()?->value ?? old('bonus.fixed_salary_per_day') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ============================================= --}}
                        {{-- Динамический рендер действий с аккордеоном --}}
                        {{-- ============================================= --}}
                        @foreach($tariffActions as $action)
                            @if($action === 'Оклад')
                                @continue
                            @endif

                                {{-- === ОДНА КАРТОЧКА НА ACTION С ВЛОЖЕННЫМИ АККОРДЕОНАМИ === --}}
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">{{ $action }}</h5>
                                    </div>
                                    <div class="card-body">

                                        {{-- === ВЛОЖЕННЫЙ АККОРДЕОН: ЗАРПЛАТА === --}}
                                        <div class="card mb-2">
                                            <div
                                                class="card-header p-2 bg-light"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#salary-{{ \Illuminate\Support\Str::slug($action) }}"
                                                style="cursor: pointer;">
                                                <h6 class="mb-0 d-flex justify-content-between align-items-center">
                                                    <span>Зарплата</span>
                                                    <i class="fas fa-chevron-right"></i>
                                                </h6>
                                            </div>
                                            <div
                                                id="salary-{{ \Illuminate\Support\Str::slug($action) }}"
                                                class="collapse">
                                                <div class="card-body">
                                                    <select
                                                        class="form-control tariff-type-select"
                                                        data-action="{{ $action }}"
                                                        data-bonus-type="salary">
                                                        <option
                                                            value="" {{ !$userTariffsSalary->get($action) || $userTariffsSalary->get($action)?->type === '' ? 'selected' : '' }}>
                                                            -не начислять-
                                                        </option>
                                                        <option
                                                            value="per_meter" {{ $userTariffsSalary->get($action)?->type === 'per_meter' ? 'selected' : '' }}>
                                                            за пог.метр
                                                        </option>
                                                        <option
                                                            value="per_piece" {{ $userTariffsSalary->get($action)?->type === 'per_piece' ? 'selected' : '' }}>
                                                            за штуку
                                                        </option>
                                                    </select>

                                                    {{-- Таблица per_meter для Зарплаты --}}
                                                    <div
                                                        class="pricing-table-salary mt-3"
                                                        data-action="{{ $action }}"
                                                        data-bonus-type="salary"
                                                        data-type="per_meter"
                                                        style="display: {{ $userTariffsSalary->get($action)?->type === 'per_meter' ? 'block' : 'none' }};">
                                                        <div
                                                            class="table-responsive">
                                                            <table
                                                                class="table table-bordered table-hover table-sm">
                                                                <thead>
                                                                <tr>
                                                                    <th>
                                                                        Материал
                                                                    </th>
                                                                    @php
                                                                        $actionRanges = $tariffRangesSalary[$action] ?? collect();
                                                                    @endphp
                                                                    @foreach($actionRanges as $index => $range)
                                                                        @php
                                                                            $limit = \App\Helpers\TariffHelper::getRangeLimit($range);
                                                                        @endphp
                                                                        <th style="min-width: 120px; max-width: 120px;"
                                                                            class="range-header"
                                                                            data-action="{{ $action }}"
                                                                            data-bonus-type="salary">
                                                                            <div
                                                                                class="d-flex align-items-center justify-content-between">
                                                                                <span
                                                                                    class="mr-1">до</span>
                                                                                <input
                                                                                    type="number"
                                                                                    class="form-control form-control-sm range-limit-input"
                                                                                    value="{{ $limit }}"
                                                                                    min="1"
                                                                                    data-action="{{ $action }}"
                                                                                    data-bonus-type="salary"
                                                                                    data-range="{{ $range }}"
                                                                                    data-index="{{ $index }}">
                                                                                <button
                                                                                    type="button"
                                                                                    class="btn btn-sm btn-link text-danger p-0 ml-1"
                                                                                    onclick="removeRangeColumn('{{ $action }}', 'salary', this)">
                                                                                    ×
                                                                                </button>
                                                                            </div>
                                                                        </th>
                                                                    @endforeach
                                                                    <th width="50">
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-sm btn-outline-primary"
                                                                            onclick="addRangeColumn('{{ $action }}', 'salary')">
                                                                            +
                                                                        </button>
                                                                    </th>
                                                                </tr>
                                                                </thead>
                                                                <tbody>
                                                                @foreach($materials as $material)
                                                                    <tr data-material-id="{{ $material->id }}">
                                                                        <td>{{ $material->title }}</td>
                                                                        @foreach($actionRanges as $range)
                                                                            @php
                                                                                $tariff = $userTariffsSalary->get($action)?->tariffs
                                                                                    ->where('range', $range)
                                                                                    ->where('material_id', $material->id)
                                                                                    ->first();
                                                                            @endphp
                                                                            <td>
                                                                                <input
                                                                                    type="number"
                                                                                    step="0.01"
                                                                                    class="form-control form-control-sm"
                                                                                    placeholder="0"
                                                                                    name="salary[tariffs][{{ $action }}][per_meter][{{ $range }}][{{ $material->id }}]"
                                                                                    value="{{ $tariff?->value ?? '' }}">
                                                                            </td>
                                                                        @endforeach
                                                                        <td></td>
                                                                    </tr>
                                                                @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>

                                                    {{-- Таблица per_piece для Зарплаты --}}
                                                    <div
                                                        class="pricing-table-salary mt-3"
                                                        data-action="{{ $action }}"
                                                        data-bonus-type="salary"
                                                        data-type="per_piece"
                                                        style="display: {{ $userTariffsSalary->get($action)?->type === 'per_piece' ? 'block' : 'none' }};">
                                                        <div
                                                            class="table-responsive">
                                                            <table
                                                                class="table table-bordered table-hover table-sm">
                                                                <thead>
                                                                <tr>
                                                                    <th>
                                                                        Материал
                                                                    </th>
                                                                    @foreach(['200', '300', '400', '500', '600', '700', '800'] as $width)
                                                                        <th style="min-width: 120px; max-width: 120px;">{{ $width }}</th>
                                                                    @endforeach
                                                                </tr>
                                                                </thead>
                                                                <tbody>
                                                                @foreach($materials as $material)
                                                                    <tr>
                                                                        <td>{{ $material->title }}</td>
                                                                        @foreach(['200', '300', '400', '500', '600', '700', '800'] as $width)
                                                                            @php
                                                                                $tariff = $userTariffsSalary->get($action)?->tariffs
                                                                                    ->where('width', $width)
                                                                                    ->where('material_id', $material->id)
                                                                                    ->first();
                                                                            @endphp
                                                                            <td>
                                                                                <input
                                                                                    type="number"
                                                                                    step="0.01"
                                                                                    class="form-control form-control-sm"
                                                                                    placeholder="0"
                                                                                    name="salary[tariffs][{{ $action }}][per_piece][{{ $width }}][{{ $material->id }}]"
                                                                                    value="{{ $tariff?->value ?? '' }}">
                                                                            </td>
                                                                        @endforeach
                                                                    </tr>
                                                                @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>

                                        {{-- === ВЛОЖЕННЫЙ АККОРДЕОН: БОНУСЫ === --}}
                                        <div class="card mb-2">
                                            <div
                                                class="card-header p-2 bg-light"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#bonus-{{ \Illuminate\Support\Str::slug($action) }}"
                                                style="cursor: pointer;">
                                                <h6 class="mb-0 d-flex justify-content-between align-items-center">
                                                    <span>Бонусы</span>
                                                    <i class="fas fa-chevron-right"></i>
                                                </h6>
                                            </div>
                                            <div
                                                id="bonus-{{ \Illuminate\Support\Str::slug($action) }}"
                                                class="collapse">
                                                <div class="card-body">
                                                    <select
                                                        class="form-control tariff-type-select"
                                                        data-action="{{ $action }}"
                                                        data-bonus-type="bonus">
                                                        <option
                                                            value="" {{ !$userTariffsBonus->get($action) || $userTariffsBonus->get($action)?->type === '' ? 'selected' : '' }}>
                                                            -не начислять-
                                                        </option>
                                                        <option
                                                            value="per_meter" {{ $userTariffsBonus->get($action)?->type === 'per_meter' ? 'selected' : '' }}>
                                                            за пог.метр
                                                        </option>
                                                        <option
                                                            value="per_piece" {{ $userTariffsBonus->get($action)?->type === 'per_piece' ? 'selected' : '' }}>
                                                            за штуку
                                                        </option>
                                                    </select>

                                                    {{-- Таблица per_meter для Бонусов --}}
                                                    <div
                                                        class="pricing-table-bonus mt-3"
                                                        data-action="{{ $action }}"
                                                        data-bonus-type="bonus"
                                                        data-type="per_meter"
                                                        style="display: {{ $userTariffsBonus->get($action)?->type === 'per_meter' ? 'block' : 'none' }};">
                                                        <div
                                                            class="table-responsive">
                                                            <table
                                                                class="table table-bordered table-hover table-sm">
                                                                <thead>
                                                                <tr>
                                                                    <th>
                                                                        Материал
                                                                    </th>
                                                                    @php
                                                                        $actionRanges = $tariffRangesBonus[$action] ?? collect();
                                                                    @endphp
                                                                    @foreach($actionRanges as $index => $range)
                                                                        @php
                                                                            $limit = \App\Helpers\TariffHelper::getRangeLimit($range);
                                                                        @endphp
                                                                        <th style="min-width: 120px; max-width: 120px;"
                                                                            class="range-header"
                                                                            data-action="{{ $action }}"
                                                                            data-bonus-type="bonus">
                                                                            <div
                                                                                class="d-flex align-items-center justify-content-between">
                                                                                <span
                                                                                    class="mr-1">до</span>
                                                                                <input
                                                                                    type="number"
                                                                                    class="form-control form-control-sm range-limit-input"
                                                                                    value="{{ $limit }}"
                                                                                    min="1"
                                                                                    data-action="{{ $action }}"
                                                                                    data-bonus-type="bonus"
                                                                                    data-range="{{ $range }}"
                                                                                    data-index="{{ $index }}">
                                                                                <button
                                                                                    type="button"
                                                                                    class="btn btn-sm btn-link text-danger p-0 ml-1"
                                                                                    onclick="removeRangeColumn('{{ $action }}', 'bonus', this)">
                                                                                    ×
                                                                                </button>
                                                                            </div>
                                                                        </th>
                                                                    @endforeach
                                                                    <th width="50">
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-sm btn-outline-primary"
                                                                            onclick="addRangeColumn('{{ $action }}', 'bonus')">
                                                                            +
                                                                        </button>
                                                                    </th>
                                                                </tr>
                                                                </thead>
                                                                <tbody>
                                                                @foreach($materials as $material)
                                                                    <tr data-material-id="{{ $material->id }}">
                                                                        <td>{{ $material->title }}</td>
                                                                        @foreach($actionRanges as $range)
                                                                            @php
                                                                                $tariff = $userTariffsBonus->get($action)?->tariffs
                                                                                    ->where('range', $range)
                                                                                    ->where('material_id', $material->id)
                                                                                    ->first();
                                                                            @endphp
                                                                            <td>
                                                                                <input
                                                                                    type="number"
                                                                                    step="0.01"
                                                                                    class="form-control form-control-sm"
                                                                                    placeholder="0"
                                                                                    name="bonus[tariffs][{{ $action }}][per_meter][{{ $range }}][{{ $material->id }}]"
                                                                                    value="{{ $tariff?->value ?? '' }}">
                                                                            </td>
                                                                        @endforeach
                                                                        <td></td>
                                                                    </tr>
                                                                @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>

                                                    {{-- Таблица per_piece для Бонусов --}}
                                                    <div
                                                        class="pricing-table-bonus mt-3"
                                                        data-action="{{ $action }}"
                                                        data-bonus-type="bonus"
                                                        data-type="per_piece"
                                                        style="display: {{ $userTariffsBonus->get($action)?->type === 'per_piece' ? 'block' : 'none' }};">
                                                        <div
                                                            class="table-responsive">
                                                            <table
                                                                class="table table-bordered table-hover table-sm">
                                                                <thead>
                                                                <tr>
                                                                    <th>
                                                                        Материал
                                                                    </th>
                                                                    @foreach(['200', '300', '400', '500', '600', '700', '800'] as $width)
                                                                        <th style="min-width: 120px; max-width: 120px;">{{ $width }}</th>
                                                                    @endforeach
                                                                </tr>
                                                                </thead>
                                                                <tbody>
                                                                @foreach($materials as $material)
                                                                    <tr>
                                                                        <td>{{ $material->title }}</td>
                                                                        @foreach(['200', '300', '400', '500', '600', '700', '800'] as $width)
                                                                            @php
                                                                                $tariff = $userTariffsBonus->get($action)?->tariffs
                                                                                    ->where('width', $width)
                                                                                    ->where('material_id', $material->id)
                                                                                    ->first();
                                                                            @endphp
                                                                            <td>
                                                                                <input
                                                                                    type="number"
                                                                                    step="0.01"
                                                                                    class="form-control form-control-sm"
                                                                                    placeholder="0"
                                                                                    name="bonus[tariffs][{{ $action }}][per_piece][{{ $width }}][{{ $material->id }}]"
                                                                                    value="{{ $tariff?->value ?? '' }}">
                                                                            </td>
                                                                        @endforeach
                                                                    </tr>
                                                                @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                            </div>
                        @endforeach

                        {{-- Скрытые поля для типов (динамически) --}}
                        @foreach($tariffActions as $action)
                            @if($action !== 'Оклад')
                                <input type="hidden"
                                       name="salary[tariffs][{{ $action }}][type]"
                                       class="tariff-type-hidden"
                                       data-action="{{ $action }}"
                                       data-bonus-type="salary"
                                       value="{{ $userTariffsSalary->get($action)?->type ?? '' }}">
                                <input type="hidden"
                                       name="bonus[tariffs][{{ $action }}][type]"
                                       class="tariff-type-hidden"
                                       data-action="{{ $action }}"
                                       data-bonus-type="bonus"
                                       value="{{ $userTariffsBonus->get($action)?->type ?? '' }}">
                            @endif
                        @endforeach

                        <button type="submit" class="btn btn-primary">Сохранить
                            тарифы
                        </button>
                    </form>
                </div>
            </div>
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
        // Инициализация Bootstrap collapse через jQuery
        $(document).ready(function () {
            $('.collapse').collapse({
                toggle: false
            });

            // Обработчик клика на заголовки аккордеона
            $('[data-bs-toggle="collapse"]').on('click', function () {
                const $header = $(this);
                const target = $header.attr('data-bs-target');
                const $icon = $header.find('i');

                $(target).collapse('toggle');

                // Переключение иконки
                $(target).on('shown.bs.collapse', function () {
                    $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                });
                $(target).on('hidden.bs.collapse', function () {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                });
            });
        });

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

    <script>
        // Переключение таблиц тарифов
        document.querySelectorAll('.tariff-type-select').forEach(select => {
            select.addEventListener('change', function () {
                const action = this.dataset.action;
                const bonusType = this.dataset.bonusType;
                const value = this.value;

                // Находим скрытое поле для этого действия
                const hiddenInput = document.querySelector(`.tariff-type-hidden[data-action="${action}"][data-bonus-type="${bonusType}"]`);
                if (hiddenInput) {
                    hiddenInput.value = value;
                }

                // Скрываем все таблицы для этого action и bonusType
                document.querySelectorAll(`.pricing-table-${bonusType}[data-action="${action}"]`).forEach(table => {
                    table.style.display = 'none';
                });

                // Показываем нужную таблицу по data-type
                if (value === 'per_meter' || value === 'per_piece') {
                    const targetTable = document.querySelector(`.pricing-table-${bonusType}[data-action="${action}"][data-type="${value}"]`);
                    if (targetTable) {
                        targetTable.style.display = 'block';
                    }
                }
            });
        });

        // === Динамические диапазоны для per_meter ===

        // Построение диапазонов из значений инпутов: [10, 100, 1000] → ['0-10', '10-100', '100-1000']
        function buildRangesFromInputs(action, bonusType) {
            const inputs = document.querySelectorAll(`.range-limit-input[data-action="${action}"][data-bonus-type="${bonusType}"]`);
            const limits = Array.from(inputs)
                .map(input => parseInt(input.value) || 0)
                .filter(v => v > 0)
                .sort((a, b) => a - b); // Сортировка по возрастанию

            const ranges = [];
            let prev = 0;
            limits.forEach(limit => {
                ranges.push(`${prev}-${limit}`);
                prev = limit;
            });
            return ranges;
        }

        // Добавление новой колонки
        function addRangeColumn(action, bonusType) {
            const table = document.querySelector(`.pricing-table-${bonusType}[data-action="${action}"][data-bonus-type="${bonusType}"] table`);
            const theadRow = table.querySelector('thead tr');
            const tbody = table.querySelector('tbody');

            // Находим индекс для нового input
            const existingInputs = document.querySelectorAll(`.range-limit-input[data-action="${action}"][data-bonus-type="${bonusType}"]`);
            const newIndex = existingInputs.length;

            // Вставляем новую колонку перед кнопкой "+"
            const addBtnTh = theadRow.lastElementChild;

            // Новый th с input
            const newTh = document.createElement('th');
            newTh.width = '120';
            newTh.className = 'range-header';
            newTh.dataset.action = action;
            newTh.dataset.bonusType = bonusType;
            newTh.innerHTML = `
                <div class="d-flex align-items-center justify-content-between">
                    <span class="mr-1">до</span>
                    <input type="number"
                           class="form-control form-control-sm range-limit-input"
                           value=""
                           min="1"
                           placeholder="100"
                           data-action="${action}"
                           data-bonus-type="${bonusType}"
                           data-index="${newIndex}">
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-1"
                            onclick="removeRangeColumn('${action}', '${bonusType}', this)">
                        ×
                    </button>
                </div>
            `;
            theadRow.insertBefore(newTh, addBtnTh);

            // Добавляем ячейки в каждую строку tbody
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                const materialId = row.dataset.materialId;

                const newTd = document.createElement('td');
                newTd.innerHTML = `
                    <input type="number"
                           step="0.01"
                           class="form-control form-control-sm"
                           placeholder="0"
                           name="${bonusType}[tariffs][${action}][per_meter][__new__][${materialId}]"
                           value="">
                `;
                row.insertBefore(newTd, row.lastElementChild);
            });

            // Фокус на новый input
            newTh.querySelector('input').focus();

            // Добавляем обработчики на новый input
            const newInput = newTh.querySelector('input');
            newInput.addEventListener('input', function () {
                handleRangeInputChange(this);
            });
            newInput.addEventListener('change', function () {
                handleRangeInputChange(this);
            });
        }

        // Удаление колонки
        function removeRangeColumn(action, bonusType, btn) {
            const table = btn.closest('table');
            const th = btn.closest('th');
            const columnIndex = Array.from(th.parentNode.children).indexOf(th);

            // Удаляем th из thead
            th.remove();

            // Удаляем ячейки из tbody
            const tbody = table.querySelector('tbody');
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                if (row.children[columnIndex]) {
                    row.children[columnIndex].remove();
                }
            });

            // Обновляем name атрибуты
            updateInputNames(action, bonusType);
        }

        // Обработчик изменения input — обновить name атрибуты
        function handleRangeInputChange(input) {
            const action = input.dataset.action;
            const bonusType = input.dataset.bonusType;

            // Запоминаем активный элемент и его значение
            const activeElement = document.activeElement;
            const activeValue = activeElement?.value;

            // Выполняем сортировку
            sortColumns(action, bonusType);
            updateInputNames(action, bonusType);

            // Восстанавливаем фокус
            if (activeValue !== undefined) {
                // Пытаемся найти тот же input по значению
                const inputs = document.querySelectorAll(`.range-limit-input[data-action="${action}"][data-bonus-type="${bonusType}"]`);
                inputs.forEach(inp => {
                    if (inp.value === activeValue) {
                        inp.focus();
                    }
                });
            }
        }

        // Сортировка колонок по возрастанию значений в thead
        function sortColumns(action, bonusType) {
            const table = document.querySelector(`.pricing-table-${bonusType}[data-action="${action}"][data-bonus-type="${bonusType}"] table`);
            const theadRow = table.querySelector('thead tr');
            const tbody = table.querySelector('tbody');

            // Сохраняем значения ДО сортировки: {materialId_colIndex: value}
            const rows = tbody.querySelectorAll('tr');
            const savedValues = {};

            rows.forEach(row => {
                const materialId = row.dataset.materialId;
                const inputs = row.querySelectorAll('input[name*="per_meter"]');
                inputs.forEach((input, colIndex) => {
                    const match = input.name.match(/per_meter\]\[([^\]]+)\]\[/);
                    if (match) {
                        const range = match[1];
                        const key = `${materialId}_${colIndex}`;
                        savedValues[key] = input.value;
                    }
                });
            });

            // Получаем все range-header th
            const headers = Array.from(theadRow.querySelectorAll('.range-header'));
            const buttonTh = theadRow.lastElementChild;

            // Создаём массив пар [th, значение, оригинальный_индекс]
            const headerData = headers.map((th, index) => {
                const input = th.querySelector('.range-limit-input');
                return {
                    th: th,
                    value: parseInt(input?.value) || 0,
                    origIndex: index
                };
            });

            // Сортируем по значению, запоминаем старые индексы
            const sortedHeaders = [...headerData].sort((a, b) => a.value - b.value);
            const newIndexMap = {}; // oldIndex -> newIndex
            sortedHeaders.forEach((item, newIndex) => {
                newIndexMap[item.origIndex] = newIndex;
            });

            // Обратный маппинг: newIndex -> oldIndex (для восстановления значений)
            const oldIndexMap = {};
            sortedHeaders.forEach((item, newIndex) => {
                oldIndexMap[newIndex] = item.origIndex;
            });

            // Удаляем все header колонки из thead
            headers.forEach(th => th.remove());

            // Вставляем отсортированные заголовки
            sortedHeaders.forEach(item => {
                theadRow.insertBefore(item.th, buttonTh);
            });

            // Сортируем ячейки в tbody по новым индексам
            rows.forEach(row => {
                const materialId = row.dataset.materialId;
                const cells = Array.from(row.children).slice(1, -1); // Все кроме первой и последней
                const lastTd = row.lastElementChild;

                // Создаём массив пар [td, oldIndex]
                const cellData = cells.map((td, index) => ({
                    td: td,
                    oldIndex: index
                }));

                // Сортируем по newIndex
                cellData.sort((a, b) => (newIndexMap[a.oldIndex] ?? 999) - (newIndexMap[b.oldIndex] ?? 999));

                // Удаляем все ячейки кроме первой и последней
                cells.forEach(td => td.remove());

                // Вставляем отсортированные ячейки
                cellData.forEach((item, newIndex) => {
                    row.insertBefore(item.td, lastTd);
                    // Восстанавливаем значение из старой позиции
                    const oldIndex = item.oldIndex;
                    const key = `${materialId}_${oldIndex}`;
                    if (savedValues[key] !== undefined) {
                        const input = item.td.querySelector('input');
                        if (input) {
                            input.value = savedValues[key];
                        }
                    }
                });
            });
        }

        // Обновление name атрибутов в tbody на основе текущих диапазонов
        function updateInputNames(action, bonusType) {
            const tbody = document.querySelector(`.pricing-table-${bonusType}[data-action="${action}"][data-bonus-type="${bonusType}"] tbody`);
            const rows = tbody.querySelectorAll('tr');

            // Строим новые диапазоны (отсортированные)
            const ranges = buildRangesFromInputs(action, bonusType);

            // Обновляем name атрибуты (значения уже на месте после sortColumns)
            rows.forEach(row => {
                const materialId = row.dataset.materialId;
                const inputs = row.querySelectorAll('input[name*="per_meter"]');

                inputs.forEach((input, index) => {
                    if (ranges[index]) {
                        input.name = `${bonusType}[tariffs][${action}][per_meter][${ranges[index]}][${materialId}]`;
                    }
                });
            });
        }

        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', function () {
            // Сортируем колонки для всех видимых per_meter таблиц (salary и bonus)
            document.querySelectorAll('.pricing-table-salary, .pricing-table-bonus').forEach(container => {
                if (container.style.display !== 'none') {
                    const action = container.dataset.action;
                    const bonusType = container.dataset.bonusType;
                    sortColumns(action, bonusType);
                    updateInputNames(action, bonusType);
                }
            });

            // Добавляем обработчики на все существующие range-limit-input
            document.querySelectorAll('.range-limit-input').forEach(input => {
                input.addEventListener('input', function () {
                    handleRangeInputChange(this);
                });
                input.addEventListener('change', function () {
                    handleRangeInputChange(this);
                });
            });
        });
    </script>
@endpush
