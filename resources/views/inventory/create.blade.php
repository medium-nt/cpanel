@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-6">
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
                @endif

                <form action="{{ route('inventory.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="inventory_shelf">Тип
                                    инвентаризации</label>
                                <select name="inventory_shelf"
                                        id="inventory_shelf"
                                        class="form-control" required>
                                    <option value="" disabled selected>Выберите
                                        тип
                                    </option>
                                    <option value="all"
                                            @if(old('inventory_shelf') == 'all') selected @endif>
                                        Полная
                                    </option>
                                    @foreach($shelfs as $shelf)
                                        <option value="{{ $shelf->id }}"
                                                @if(old('inventory_shelf') == $shelf->id) selected @endif>
                                            Только полка "{{ $shelf->title }}"
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="comment">Комментарии</label>
                                <textarea
                                    class="form-control @error('comment') is-invalid @enderror"
                                    id="comment"
                                    name="comment"
                                    rows="3"
                                >{{ old('comment') }}</textarea>
                                @error('comment')
                                <span
                                    class="invalid-feedback d-block mt-0">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                Сохранить
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
@stop
