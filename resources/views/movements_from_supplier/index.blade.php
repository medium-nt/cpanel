@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('movements_from_supplier.create') }}" class="btn btn-primary mr-3 mb-3">Добавить новое поступление</a>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Материал</th>
                            <th scope="col">Кол-во</th>
                            <th scope="col">Комментарии</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Дата</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($movements as $movement)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $movement->material->title }}</td>
                                <td>{{ $movement->quantity }} {{ $movement->material->unit }}</td>
                                <td>{{ $movement->comment }}</td>
                                <td>{{ $movement->status_name }}</td>
                                <td>{{ now()->parse($movement->created_at)->format('d/m/Y') }}</td>

                                <td style="width: 100px">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('movements_from_supplier.edit', ['movement' => $movement->id]) }}" class="btn btn-primary mr-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                <x-pagination-component :collection="$movements" />

            </div>
        </div>
    </div>
@stop

{{-- Push extra CSS --}}

@push('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@endpush

{{-- Push extra scripts --}}

@push('js')
    {{--    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>--}}
@endpush
