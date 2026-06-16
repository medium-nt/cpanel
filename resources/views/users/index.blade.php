@php

    use App\Services\UserService;

@endphp

@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('users.create') }}" class="btn btn-primary mr-3 mb-3">Добавить сотрудника</a>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <select name="role_id" class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">Все роли</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}"
                                        @if(request('role_id') == $role->id) selected @endif>
                                    {{ UserService::translateRoleName($role->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="workshop_id" class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">Все цеха</option>
                            @foreach ($workshops as $workshop)
                                <option value="{{ $workshop->id }}"
                                        @if(request('workshop_id') == $workshop->id) selected @endif>
                                    {{ $workshop->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Аватар</th>
                            <th scope="col">Имя</th>
                            <th scope="col">Роль</th>
                            <th scope="col">Цех</th>
                            <th scope="col">Email / Телефон</th>
                            <th scope="col">Создан</th>
                            <th scope="col">Обновлен</th>
                            <th scope="col">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    @if($user->avatar != null)
                                    <img src="{{ asset('storage/' . $user->avatar) }}"
                                         style="width:50px; height:50px;" alt="">
                                    @endif
                                </td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->role ? UserService::translateRoleName($user->role->name) : 'Не указана' }}</td>
                                <td>{{ $user->currentWorkshop()?->title ?? '—' }}</td>
                                <td>{{ $user->email }} <br> {{ $user->phone }}
                                </td>
                                <td>{{ $user->created_date }}</td>
                                <td>{{ $user->updated_date }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                    <a href="{{ route('users.edit', ['user' => $user->id]) }}" class="btn btn-primary mr-1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                        <form action="{{ route('users.destroy', ['user' => $user->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                    onclick="return confirm('Вы уверены что хотите удалить данного сотрудника?')">
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
                <x-pagination-component :collection="$users" />

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
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
