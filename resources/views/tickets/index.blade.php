@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('tickets.create') }}"
                   class="btn btn-primary mr-3 mb-3">
                    <i class="fas fa-plus"></i> Новый тикет
                </a>

                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link {{ $scope === 'new' ? 'active' : '' }}"
                           href="{{ route('tickets.index', ['scope' => 'new']) }}">
                            Новые
                            @if ($newCount > 0)
                                <span
                                    class="badge badge-danger ml-1">{{ $newCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $scope === 'processed' ? 'active' : '' }}"
                           href="{{ route('tickets.index', ['scope' => 'processed']) }}">
                            Обработанные
                            @if ($processedCount > 0)
                                <span
                                    class="badge badge-secondary ml-1">{{ $processedCount }}</span>
                            @endif
                        </a>
                    </li>
                </ul>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Описание</th>
                            @can('is-admin')
                                <th scope="col">Автор</th>
                            @endcan
                            <th scope="col">Статус</th>
                            <th scope="col">Дата</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($tickets as $ticket)
                            @php
                                // Непрочитанный ответ автора: подсветка строки + иконка-индикатор.
                                $hasUnreadAnswer = $ticket->admin_comment && ! $ticket->answer_read_at;
                            @endphp
                            <tr class="{{ $hasUnreadAnswer ? 'table-warning' : '' }}">
                                <td>{{ $ticket->id }}</td>
                                <td>
                                    @if ($hasUnreadAnswer)
                                        <i class="fas fa-comment-dots text-warning"
                                           title="Новый ответ администратора"></i>
                                    @endif
                                    {{ \Illuminate\Support\Str::limit($ticket->description, 80) }}
                                </td>
                                @can('is-admin')
                                    <td>{{ $ticket->user?->name }}</td>
                                @endcan
                                <td>
                                    <span
                                        class="badge {{ \App\Models\Ticket::BADGE_COLORS[$ticket->status] ?? 'badge-secondary' }}">
                                        {{ \App\Models\Ticket::STATUSES[$ticket->status] ?? $ticket->status }}
                                    </span>
                                </td>
                                <td>{{ $ticket->created_at->format('d.m.Y H:i') }}</td>
                                <td style="width: 80px">
                                    <a href="{{ route('tickets.show', $ticket) }}"
                                       class="btn btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->isAdmin() ? 6 : 5 }}"
                                    class="text-center text-muted">
                                    Тикетов нет
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <x-pagination-component :collection="$tickets"/>
            </div>
        </div>
    </div>
@stop
