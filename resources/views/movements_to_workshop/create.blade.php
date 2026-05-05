@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-12">
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

            <form action="{{ route('movements_to_workshop.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label for="material_id">Материал</label>
                            <select name="material_id" class="form-control"
                                    required>
                                <option value="" selected>---</option>
                                @foreach($materials as $material)
                                    <option
                                        value="{{ $material->id }}">{{ $material->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label for="comment">Комментарий</label>
                            <textarea name="comment"
                                      class="form-control"
                                      rows="3"
                                      minlength="3"
                            ></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Оформить
                            заказ
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        const form = document.querySelector('form[action*="movements_to_workshop"]');
        const button = form.querySelector('button');

        form.addEventListener('submit', function () {
            button.disabled = true;
            button.textContent = 'Оформление...';
        });
    </script>
@stop
