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

            <form action="{{ route('defect_materials.store') }}" method="POST" onsubmit="return validateQuantity()">
                @method('POST')
                @csrf
                <div class="card-body">

                    @livewire('material-form', ['sourceType' => 'workshop', 'typeMovement' => request('type_movement_id')])

                    <div class="form-group">
                        <label for="comment">Комментарий</label>
                        <textarea class="form-control @error('comment') is-invalid @enderror"
                                  id="comment"
                                  name="comment"
                                  rows="3"
                                  minlength="3"
                                  value="{{ old('comment') }}"
                        ></textarea>
                    </div>

                    <input type="hidden" name="type_movement_id" value="{{ request('type_movement_id') }}">

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Принять</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        function validateQuantity() {
            const inputs = document.querySelectorAll('input[name="ordered_quantity[]"]');

            for (const input of inputs) {
                const value = parseFloat(input.value);

                if (isNaN(value)) {
                    alert('Заполните поле "Количество"');
                    input.classList.add('is-invalid');
                    return false;
                }

                if (value > 10) {
                    const confirmed = confirm('Вы уверены, что хотите отправить брак более 10 метров?');
                    if (!confirmed) {
                        input.classList.add('is-invalid');
                        return false;
                    }
                }

                input.classList.remove('is-invalid');
            }

            return true;
        }
    </script>
@endsection
