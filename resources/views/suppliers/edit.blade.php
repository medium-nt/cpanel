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
                    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Вернуться в список поставщиков
                    </a>
                </h3>
            </div>

            <form action="{{ route('suppliers.update', ['supplier' => $supplier->id]) }}" method="POST">
                @method('PUT')
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Название</label>
                        <input type="text"
                               class="form-control @error('title') is-invalid @enderror"
                               id="title"
                               name="title"
                               value="{{ $supplier->title }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="text"
                               class="form-control @error('phone') is-invalid @enderror"
                               id="phone"
                               name="phone"
                               value="{{ $supplier->phone }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="address">Адрес</label>
                        <input type="text"
                               class="form-control @error('address') is-invalid @enderror"
                               id="address"
                               name="address"
                               value="{{ $supplier->address }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="comment">Комментарий</label>
                        <textarea class="form-control @error('comment') is-invalid @enderror"
                                  id="comment"
                                  name="comment"
                                  rows="5"
                        >{{ $supplier->comment }}</textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Сохранить</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
