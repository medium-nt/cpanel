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

            <form action="{{ route('movements_defect_to_supplier.store') }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="supplier_id">Поставщик</label>
                        <select name="supplier_id" id="supplier_id" class="form-control" required>
                            <option value="" disabled selected>---</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    @livewire('material-form', ['isFirst' => true, 'sourceType' => 'defect'])
                    @livewire('material-form', ['sourceType' => 'defect'])
                    @livewire('material-form', ['sourceType' => 'defect'])
                    @livewire('material-form', ['sourceType' => 'defect'])
                    @livewire('material-form', ['sourceType' => 'defect'])

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
                        <button class="btn btn-primary">Оформить заказ</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const selects = document.querySelectorAll('select[name="material_id[]"]');

            function checkUniqueSelects() {
                let selectedValues = [];

                selects.forEach(select => {
                    if (select.value !== "") {
                        if (selectedValues.includes(select.value)) {
                            select.value = "";
                            alert("Этот материал уже выбран!");
                        } else {
                            selectedValues.push(select.value);
                        }
                    }
                });
            }

            selects.forEach(select => {
                select.addEventListener('change', checkUniqueSelects);
            });
        });
    </script>
@stop
