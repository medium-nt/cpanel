@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('shelves.create') }}"
                   class="btn btn-primary mr-3 mb-3">Добавить полку</a>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Название</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($shelves as $shelf)
                            <tr>
                                <td style="width: 50px">{{ $shelf->id }}</td>
                                <td>{{ $shelf->title }}</td>

                                <td style="width: 100px">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('shelves.edit', ['shelf' => $shelf->id]) }}"
                                           class="btn btn-primary mr-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form
                                            action="{{ route('shelves.destroy', ['shelf' => $shelf->id]) }}"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-danger"
                                                    onclick="return confirm('Вы уверены что хотите удалить данную полку из склада?')">
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
                <x-pagination-component :collection="$shelves"/>

            </div>
        </div>
    </div>
@stop
