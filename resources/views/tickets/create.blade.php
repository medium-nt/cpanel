@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif()

                <form action="{{ route('tickets.store') }}" method="POST"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="description">Опишите проблему <span
                                class="text-danger">*</span></label>
                        <textarea id="description"
                                  name="description"
                                  rows="5"
                                  class="form-control @error('description') is-invalid @enderror"
                                  required>{{ old('description') }}</textarea>
                        <small class="text-muted">Что не работает, что ожидали,
                            что произошло.</small>
                        @error('description')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="page_url">URL страницы с проблемой <small
                                class="text-muted">(необязательно)</small></label>
                        <input type="url"
                               id="page_url"
                               name="page_url"
                               class="form-control @error('page_url') is-invalid @enderror"
                               value="{{ old('page_url', $pageUrl) }}">
                        @error('page_url')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="screenshot">Скриншот ошибки <small
                                class="text-muted">(необязательно)</small></label>
                        <input type="file"
                               name="screenshot"
                               id="screenshot"
                               class="form-control @error('screenshot') is-invalid @enderror"
                               accept="image/*">
                        <img id="screenshot-preview" src="" alt=""
                             style="max-height: 160px; margin-top: 8px; display: none;">
                        @error('screenshot')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            Отправить
                        </button>
                        <a href="{{ route('tickets.index') }}"
                           class="btn btn-secondary ml-2">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.getElementById('screenshot');
            const preview = document.getElementById('screenshot-preview');
            const form = document.querySelector('form');

            if (fileInput) {
                fileInput.addEventListener('change', function () {
                    const file = fileInput.files[0];
                    if (!file) {
                        preview.style.display = 'none';
                        return;
                    }
                    preview.onload = function () {
                        preview.style.display = '';
                    };
                    preview.src = URL.createObjectURL(file);
                });
            }

            if (form) {
                form.addEventListener('submit', function () {
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
                    }
                });
            }
        });
    </script>
@stop
