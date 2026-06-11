@php
    use App\Services\UserService;
@endphp

@extends('layouts.app')

@section('subtitle', $shift->name)
@section('content_header_title', $shift->name)

@section('content_body')
    <div class="row">
        {{-- Информация о смене --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Информация</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('shifts.update', $shift) }}"
                          method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name">Название</label>
                            <input type="text" name="name" id="name"
                                   class="form-control"
                                   value="{{ $shift->name }}" required>
                        </div>

                        <div class="form-group">
                            <label for="workshop_id">Цех</label>
                            @if($hasFutureSchedule)
                                <select name="workshop_id" id="workshop_id"
                                        class="form-control" disabled>
                                    @foreach ($workshops as $workshop)
                                        <option value="{{ $workshop->id }}"
                                            {{ $shift->workshop_id === $workshop->id ? 'selected' : '' }}>
                                            {{ $workshop->title }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="workshop_id"
                                       value="{{ $shift->workshop_id }}">
                                <small class="text-danger mt-1 d-block">
                                    <i class="fas fa-lock"></i>
                                    Цех нельзя изменить: есть расписание на
                                    будущие дни
                                </small>
                            @else
                                <select name="workshop_id" id="workshop_id"
                                        class="form-control">
                                    @foreach ($workshops as $workshop)
                                        <option value="{{ $workshop->id }}"
                                            {{ $shift->workshop_id === $workshop->id ? 'selected' : '' }}>
                                            {{ $workshop->title }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="status">Статус</label>
                            <select name="status" id="status"
                                    class="form-control">
                                <option
                                    value="active" {{ $shift->status === 'active' ? 'selected' : '' }}>
                                    Активна
                                </option>
                                <option
                                    value="inactive" {{ $shift->status === 'inactive' ? 'selected' : '' }}>
                                    Неактивна
                                </option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Сотрудники --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Сотрудники ({{ $employees->count() }}
                        )</h3>
                </div>
                <div class="card-body">
                    {{-- Форма добавления --}}
                    <form action="{{ route('shifts.users.attach', $shift) }}"
                          method="POST" class="form-inline mb-3">
                        @csrf
                        <div class="form-group mr-2"
                             style="position: relative; display: inline-block;">
                            <input type="text" name="user_search"
                                   id="user_search"
                                   class="form-control"
                                   placeholder="Поиск сотрудника..."
                                   style="width: 250px;" autocomplete="off">
                            <input type="hidden" name="user_id" id="user_id">
                            <div id="user-search-results"></div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Добавить
                        </button>
                    </form>

                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Имя</th>
                            <th>Роль</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($employees as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->role ? UserService::translateRoleName($user->role->name) : '-' }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        {{-- Перевод в другую смену --}}
                                        <button type="button"
                                                class="btn btn-warning btn-sm mr-3"
                                                onclick="openTransferModal({{ $user->id }}, '{{ $user->name }}')">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                        {{-- Удаление из смены --}}
                                        <form
                                            action="{{ route('shifts.users.detach', [$shift, $user]) }}"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Удалить сотрудника из смены?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        @if($employees->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    Нет сотрудников
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Уходящие сотрудники --}}
            @if($outgoing->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-header bg-danger text-white">
                        <h3 class="card-title"><i
                                class="fas fa-sign-out-alt"></i> Уходящие
                            сотрудники</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-dark">
                            <tr>
                                <th>Сотрудник</th>
                                <th>Роль</th>
                                <th>Дата ухода</th>
                                <th>Переходит в</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($outgoing as $item)
                                <tr class="table-danger">
                                    <td>{{ $item->user->name }}</td>
                                    <td>{{ $item->user->role ? UserService::translateRoleName($item->user->role->name) : '-' }}</td>
                                    <td>
                                            <span class="badge badge-danger">
                                                с {{ \Carbon\Carbon::parse($item->effective_from)->translatedFormat('d F Y') }}
                                            </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('shifts.show', $item->to_shift) }}">{{ $item->to_shift->name }}</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Запланированные переводы --}}
            @if($incoming->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-header bg-warning">
                        <h3 class="card-title"><i
                                class="fas fa-sign-in-alt"></i> Приходящие
                            сотрудники</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-dark">
                            <tr>
                                <th>Сотрудник</th>
                                <th>Роль</th>
                                <th>Дата вступления</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($incoming as $user)
                                <tr class="table-warning">
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->role ? UserService::translateRoleName($user->role->name) : '-' }}</td>
                                    <td>
                                            <span class="badge badge-warning">
                                                с {{ \Carbon\Carbon::parse($user->pivot->effective_from)->translatedFormat('d F Y') }}
                                            </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- История --}}
            @if($history->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history"></i>
                            История перемещений</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table
                            class="table table-hover table-bordered table-sm">
                            <thead class="thead-dark">
                            <tr>
                                <th>Сотрудник</th>
                                <th>Смена</th>
                                <th>Дата вступления</th>
                                <th>Статус</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($history as $record)
                                @php
                                    $date = \Carbon\Carbon::parse($record->effective_from);
                                    $isFuture = $date->isFuture();
                                    $isToday = $date->isToday();
                                    $isCurrentShift = $record->shift_id === $shift->id;
                                @endphp
                                <tr class="{{ $isFuture ? 'table-warning' : '' }}">
                                    <td>{{ $record->user_name }}</td>
                                    <td>
                                        @if($isCurrentShift)
                                            <strong>{{ $record->shift_name }}</strong>
                                        @else
                                            {{ $record->shift_name }}
                                        @endif
                                    </td>
                                    <td>{{ $date->translatedFormat('d.m.Y') }}</td>
                                    <td>
                                        @if($isFuture)
                                            <span class="badge badge-warning">Запланирован</span>
                                        @else
                                            <span class="badge badge-success">Вступил</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isFuture)
                                            <form
                                                action="{{ route('shifts.records.destroy', [$shift, $record->id]) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Удалить эту запись?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Модальное окно перевода --}}
    <div class="modal fade" id="transferModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="transferForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Перевод сотрудника</h5>
                        <button type="button" class="close"
                                data-dismiss="modal"><span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Перевести <strong id="transferUserName"></strong> в
                            другую смену?</p>
                        <div class="form-group">
                            <label>Новая смена</label>
                            <select name="new_shift_id" id="transferShiftSelect"
                                    class="form-control" required>
                                @foreach (\App\Models\Shift::active()->get() as $s)
                                    <option
                                        value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Дата перевода</label>
                            <input type="date" name="effective_from"
                                   id="transferDate"
                                   class="form-control"
                                   min="{{ \Carbon\Carbon::today()->toDateString() }}"
                                   required>
                            <small class="text-muted">Сотрудник будет числиться
                                в новой смене начиная с этой даты</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">Отмена
                        </button>
                        <button type="submit" class="btn btn-warning">
                            Перевести
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@push('css')
    <style>
        #user-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            width: 250px;
            display: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
        }

        #user-search-results div {
            padding: 8px 12px;
            cursor: pointer;
        }

        #user-search-results div:hover {
            background: #007bff;
            color: #fff;
        }
    </style>
@endpush

@push('js')
    <script>
        $(function () {
            var $search = $('#user_search');
            var $results = $('#user-search-results');
            var $userId = $('#user_id');
            var timer = null;

            $search.on('input', function () {
                var q = $(this).val().trim();
                $userId.val('');

                if (q.length < 2) {
                    $results.hide().empty();
                    return;
                }

                clearTimeout(timer);
                timer = setTimeout(function () {
                    $.get('{{ route('shifts.search-users') }}', {q: q}, function (data) {
                        $results.empty();
                        if (data.length === 0) {
                            $results.html('<div class="text-muted">Не найдено</div>');
                        } else {
                            data.forEach(function (user) {
                                $results.append('<div data-id="' + user.id + '">' + user.name + '</div>');
                            });
                        }
                        $results.show();
                    });
                }, 300);
            });

            $results.on('click', 'div[data-id]', function () {
                var id = $(this).data('id');
                var name = $(this).text();
                $search.val(name);
                $userId.val(id);
                $results.hide();
            });

            $(document).on('click', function (e) {
                if (!$(e.target).closest('#user_search, #user-search-results').length) {
                    $results.hide();
                }
            });
        });

        function openTransferModal(userId, userName) {
            document.getElementById('transferUserName').textContent = userName;
            var form = document.getElementById('transferForm');
            form.action = '{{ route('shifts.users.transfer', [$shift, ':userId']) }}'.replace(':userId', userId);
            $('#transferModal').modal('show');
        }
    </script>
@endpush
