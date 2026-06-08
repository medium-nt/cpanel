@extends('layouts.app')

@section('subtitle', 'Создание смены')
@section('content_header_title', 'Создание смены')

@section('content_body')
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('shifts.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="name">Название смены</label>
                        <input type="text" name="name" id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required>
                        @error('name')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="workshop_id">Цех</label>
                        <select name="workshop_id" id="workshop_id"
                                class="form-control @error('workshop_id') is-invalid @enderror"
                                required>
                            <option value="">Выберите цех</option>
                            @foreach ($workshops as $workshop)
                                <option value="{{ $workshop->id }}"
                                    {{ old('workshop_id') == $workshop->id ? 'selected' : '' }}>
                                    {{ $workshop->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('workshop_id')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Создать
                    </button>
                    <a href="{{ route('shifts.index') }}"
                       class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>
@stop
