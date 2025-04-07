@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('marketplace_items.create') }}" class="btn btn-primary mr-3 mb-3">Добавить товар</a>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Название</th>
                            <th scope="col">SKU</th>
                            <th scope="col">Ширина</th>
                            <th scope="col">Высота</th>
                            <th scope="col">Маркетплейс</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->title }}</td>
                                <td>{{ $item->sku }}</td>
                                <td>{{ $item->width }}</td>
                                <td>{{ $item->height }}</td>
                                <td>{{ $item->marketplace_id }}</td>

                                <td style="width: 100px">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('marketplace_items.edit', ['marketplace_item' => $item->id]) }}" class="btn btn-primary mr-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('marketplace_items.destroy', ['marketplace_item' => $item->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                    onclick="return confirm('Вы уверены что хотите удалить данный материал из системы?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                <x-pagination-component :collection="$items" />

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
