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

            <form action="{{ route('movements_to_workshop.store') }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">
                    @livewire('material-form')
                    @livewire('material-form')
                    @livewire('material-form')
                    @livewire('material-form')
                    @livewire('material-form')

                    <div class="form-group">
                        <button class="btn btn-primary">Оформить заказ</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
