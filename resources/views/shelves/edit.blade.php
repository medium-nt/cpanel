@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
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

            <div class="card-header">
                <h3 class="card-title">
                    <a href="{{ route('shelves.index') }}"
                       class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Вернуться в список
                        полок
                    </a>
                </h3>
            </div>

            <form
                action="{{ route('shelves.update', ['shelf' => $shelf->id]) }}"
                method="POST">
                @method('PUT')
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Название</label>
                        <input type="text"
                               class="form-control @error('title') is-invalid @enderror"
                               id="title"
                               name="title"
                               value="{{ $shelf->title }}"
                               required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            Сохранить
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
