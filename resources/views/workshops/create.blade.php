@extends('layouts.app')

@section('subtitle', 'Создать цех')
@section('content_header_title', 'Создать цех')

@section('content_body')
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('workshops.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="title">Название цеха</label>
                        <input type="text"
                               name="title"
                               id="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}"
                               placeholder="Например: Цех №2"
                               required>
                        @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <a href="{{ route('workshops.index') }}"
                           class="btn btn-secondary">
                            Отмена
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Создать
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
