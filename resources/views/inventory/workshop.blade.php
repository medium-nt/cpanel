@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Материал</th>
                            <th scope="col">Количество</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($materials as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item['material']->title }}</td>
                                <td>{{ $item['quantity'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@stop
