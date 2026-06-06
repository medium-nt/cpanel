@extends('layouts.app')

@section('subtitle', 'Редактировать цех')
@section('content_header_title', 'Редактировать цех')

@section('content_body')
    <div class="col-md-6">
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
