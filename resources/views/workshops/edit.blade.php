@extends('layouts.app')

@section('subtitle', 'Редактировать цех')
@section('content_header_title', 'Редактировать цех')

@section('content_body')
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('workshops.update', $workshop) }}"
                      method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="title">Название цеха</label>
                        <input type="text"
                               name="title"
                               id="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $workshop->title) }}"
                               required>
                        @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="status">Статус</label>
                        <select name="status"
                                id="status"
                                class="form-control @error('status') is-invalid @enderror">
                            <option
                                value="active" {{ $workshop->status === 'active' ? 'selected' : '' }}>
                                Активен
                            </option>
                            <option
                                value="inactive" {{ $workshop->status === 'inactive' ? 'selected' : '' }}>
                                Неактивен
                            </option>
                        </select>
                        @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Информация о сменах цеха (только для чтения) --}}
                    @if($workshop->shifts->isNotEmpty())
                        <div class="form-group">
                            <label>Смены в этом цехе</label>
                            <ul class="list-group">
                                @foreach ($workshop->shifts as $shift)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $shift->name }}
                                        <span
                                            class="badge badge-primary badge-pill">
                                        {{ $shift->users_count }} сотр.
                                    </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Разрешённые товары маркетплейсов (группировка по названию материала) --}}
                    <div class="form-group">
                        <label>Разрешённые материалы</label>
                        <div class="mb-2">
                            <small class="text-muted">
                                Отмеченные материалы будут доступны для взятия в
                                работу в этом цехе (все ширины и высоты).
                            </small>
                        </div>
                        <div class="border rounded p-2"
                             style="max-height: 300px; overflow-y: auto;">
                            @foreach ($materialTitles as $material)
                                <div class="form-check">
                                    <input type="checkbox"
                                           name="allowed_materials[]"
                                           value="{{ $material->title }}"
                                           id="material_{{ \Illuminate\Support\Str::slug($material->title) }}"
                                           class="form-check-input"
                                        {{ in_array($material->title, $allowedTitles) ? 'checked' : '' }}>
                                    <label
                                        for="material_{{ \Illuminate\Support\Str::slug($material->title) }}"
                                           class="form-check-label">
                                        {{ $material->title }}
                                    </label>
                                </div>
                            @endforeach
                            @if($materialTitles->isEmpty())
                                <p class="text-muted text-center mb-0">Нет
                                    материалов</p>
                            @endif
                        </div>
                    </div>

                    {{-- Цеховые настройки (переопределение глобальных) --}}
                    @if($globalSettings->isNotEmpty())
                        <div class="form-group">
                            <label>Настройки цеха</label>
                            <div class="mb-2">
                                <small class="text-muted">
                                    Оставьте поле пустым — используется
                                    глобальное значение (указано справа).
                                    Заполните — значение переопределяется для
                                    этого цеха.
                                </small>
                            </div>
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                <tr>
                                    <th>Параметр</th>
                                    <th>Цеховое значение</th>
                                    <th>Глобальное</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($globalSettings as $name => $globalValue)
                                    <tr>
                                        <td>
                                            <label for="setting_{{ $name }}"
                                                   class="mb-0">
                                                {{ $settingLabels[$name] ?? $name }}
                                            </label>
                                        </td>
                                        <td>
                                            <input type="text"
                                                   name="settings[{{ $name }}]"
                                                   id="setting_{{ $name }}"
                                                   class="form-control form-control-sm"
                                                   value="{{ old("settings.$name", $workshopSettings[$name] ?? '') }}"
                                                   placeholder="{{ $globalValue }}">
                                        </td>
                                        <td class="align-middle">
                                            <input type="text"
                                                   class="form-control form-control-sm"
                                                   value="{{ $globalValue }}"
                                                   readonly
                                                   style="background-color: #f8f9fa;">
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="form-group">
                        <a href="{{ route('workshops.index') }}"
                           class="btn btn-secondary">
                            Отмена
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
