@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">

        <div class="card">
            <div class="card-body">
                <a href="{{ back()->getTargetUrl() }}" class="btn btn-default">Назад</a>
                <br>
                <br>
                Рулон: {{ $roll->roll_code }} <br>
                Материал: {{ $roll->material->title }}<br>
                Начальное
                количество: {{ $roll->initial_quantity }} {{ $roll->material->unit }}
                <br>
            </div>
        </div>

        <div class="card only-on-desktop">
            <div class="card-header">
                <h3 class="card-title">Расход</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Название</th>
                            <th scope="col">шт./п.м.</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($roll->movementMaterials as $movementMaterial)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $movementMaterial['material']->title }}</td>
                                <td>{{ $movementMaterial['quantity'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
@stop

