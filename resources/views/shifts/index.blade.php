@extends('layouts.app')

@section('subtitle', 'Смены')
@section('content_header_title', 'Смены')

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('shifts.create') }}"
                   class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Создать смену
                </a>
                <a href="{{ route('shift-schedule.index') }}"
                   class="btn btn-info mb-3">
                    <i class="fas fa-calendar"></i> Календарь смен
                </a>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Название</th>
                            <th>Статус</th>
                            <th>Сотрудники</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($shifts as $shift)
                            <tr>
                                <td>{{ $shift->id }}</td>
                                <td>{{ $shift->name }}</td>
                                <td>
                                    @if($shift->status === 'active')
                                        <span class="badge badge-success">Активна</span>
                                    @else
                                        <span class="badge badge-secondary">Неактивна</span>
                                    @endif
                                </td>
                                <td>{{ $shift->users_count }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('shifts.show', $shift) }}"
                                           class="btn btn-info mr-1">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($shift->status === 'active')
                                            <form
                                                action="{{ route('shifts.destroy', $shift) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-danger"
                                                        onclick="return confirm('Деактивировать смену?')">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        @if($shifts->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Смены не созданы
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
