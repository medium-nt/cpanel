@extends('layouts.app')

@section('subtitle', 'Цеха')
@section('content_header_title', 'Цеха')

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('workshops.create') }}"
                   class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Создать цех
                </a>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Название</th>
                            <th>Статус</th>
                            <th>Смены</th>
                            <th>Сотрудники</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($workshops as $workshop)
                            {{-- Считаем общее количество сотрудников во всех сменах цеха --}}
                            @php($employeesCount = $workshop->shifts->sum('users_count'))
                            <tr>
                                <td>{{ $workshop->id }}</td>
                                <td>{{ $workshop->title }}</td>
                                <td>
                                    @if($workshop->status === 'active')
                                        <span class="badge badge-success">Активен</span>
                                    @else
                                        <span class="badge badge-secondary">Неактивен</span>
                                    @endif
                                </td>
                                <td>{{ $workshop->shifts_count }}</td>
                                <td>{{ $employeesCount }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('workshops.edit', $workshop) }}"
                                           class="btn btn-info mr-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($workshop->status === 'active' && $workshop->shifts_count === 0)
                                            <form
                                                action="{{ route('workshops.destroy', $workshop) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-warning"
                                                        onclick="return confirm('Деактивировать цех?')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        @if($workshops->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Цеха не созданы
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
