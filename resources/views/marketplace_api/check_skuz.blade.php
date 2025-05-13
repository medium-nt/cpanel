@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('setting.index') }}" class="btn btn-primary mb-3 mr-3">
                    Вернуться в настройки
                </a>

                <a href="{{ route('marketplace_api.checkSkuz') }}" class="btn btn-outline-primary mb-3">
                    <i class="fas fa-redo mr-1"></i> Повторить проверку
                </a>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Маркетплейс</th>
                            <th scope="col">Название</th>
                            <th scope="col">skus</th>
                            <th scope="col">nmID</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($skuz as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item['marketplace_id'] }}</td>
                                <td>{{ $item['title'] }}</td>
                                <td>{{ $item['skus'][0] }}</td>
                                <td>{{ $item['nmID'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@stop
