@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        @foreach ($sections as $section)
            <div class="card mb-3">
                <div
                    class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">{{ $section['title'] }}</h3>
                    <span class="badge badge-secondary">{{ count($section['items']) }} поз.</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-dark">
                            <tr>
                                <th scope="col">Материал</th>
                                <th scope="col">Кол-во</th>
                                <th scope="col">Рулоны</th>
                                <th scope="col">Статус</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($section['items'] as $item)
                                <tr>
                                    <td>{{ $item['material']->title }}</td>
                                    <td>
                                        <span
                                            style="width: 100px; display: inline-block;">
                                            {{ $item['quantity'] }} {{ $item['material']->unit }} <br>
                                        </span>
                                    </td>
                                    <td>{{ $item['rolls_count'] }} шт.</td>
                                    <td>
                                        @if($item['quantity'] <= 300)
                                            <span class="badge badge-danger">
                                                мало
                                            </span>
                                        @elseif($item['quantity'] <= 1000)
                                            <span class="badge badge-warning">
                                                достаточно
                                            </span>
                                        @else
                                            <span class="badge badge-success">
                                                много
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@stop
