@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('hangers.create') }}"
                   class="btn btn-primary mr-3 mb-3">Добавить вешалку</a>

                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

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
                        @foreach ($hangers as $hanger)
                            <tr>
                                <td style="width: 50px">{{ $hanger->id }}</td>
                                <td>{{ $hanger->title }}</td>

                                <td style="width: 100px">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('hangers.edit', ['hanger' => $hanger->id]) }}"
                                           class="btn btn-primary mr-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form
                                            action="{{ route('hangers.destroy', ['hanger' => $hanger->id]) }}"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-danger"
                                                    onclick="return confirm('Вы уверены что хотите удалить данную вешалку?')">
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
                <x-pagination-component :collection="$hangers"/>

            </div>
        </div>
    </div>
@stop
