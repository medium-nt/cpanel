@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-10">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('tickets.index') }}"
                   class="btn btn-outline-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> Назад к списку
                </a>

                <div
                    class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <div>
                        <h4 class="mb-1">Тикет #{{ $ticket->id }}</h4>
                        <span
                            class="badge {{ \App\Models\Ticket::BADGE_COLORS[$ticket->status] ?? 'badge-secondary' }}">
                            {{ \App\Models\Ticket::STATUSES[$ticket->status] ?? $ticket->status }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap align-items-center">
                        @can('start', $ticket)
                            <form action="{{ route('tickets.start', $ticket) }}"
                                  method="POST" class="mr-2 mb-2">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-warning"
                                        onclick="return confirm('Взять тикет #{{ $ticket->id }} в работу?')">
                                    <i class="fas fa-play"></i> В работу
                                </button>
                            </form>
                        @endcan

                        @can('delete', $ticket)
                            <form
                                action="{{ route('tickets.delete', $ticket) }}"
                                method="POST" class="mb-2">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-secondary"
                                        onclick="return confirm('Переместить тикет #{{ $ticket->id }} в корзину?')">
                                    <i class="fas fa-trash"></i> В корзину
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>

                @can('close', $ticket)
                    <form action="{{ route('tickets.close', $ticket) }}"
                          method="POST" class="mb-4">
                        @csrf
                        @method('PUT')
                        <div class="d-flex align-items-center">
                            <textarea name="admin_comment"
                                      class="form-control flex-grow-1 mr-2"
                                      rows="1" maxlength="5000" required
                                      placeholder="Комментарий администратора (что сделано)"></textarea>
                            <button type="submit"
                                    class="btn btn-success text-nowrap"
                                    onclick="return confirm('Закрыть тикет #{{ $ticket->id }}?')">
                                <i class="fas fa-check"></i> Закрыть тикет
                            </button>
                        </div>
                    </form>
                @endcan

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px">Автор</th>
                            <td>
                                @if ($ticket->user)
                                    <a href="{{ route('users.edit', $ticket->user) }}">{{ $ticket->user->name }}</a>
                                    @if ($ticket->user->role)
                                        <span class="badge badge-info ml-1">
                                            {{ \App\Services\UserService::translateRoleName($ticket->user->role->name) }}
                                        </span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Дата создания</th>
                            <td>{{ $ticket->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                        @if ($ticket->closed_at)
                            <tr>
                                <th>Дата закрытия</th>
                                <td>{{ $ticket->closed_at->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endif()
                        @if ($ticket->page_url)
                            <tr>
                                <th>Страница</th>
                                <td><a href="{{ $ticket->page_url }}"
                                       target="_blank"
                                       style="word-break: break-all;">{{ $ticket->page_url }}</a>
                                </td>
                            </tr>
                        @endif()
                    </table>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Описание</label>
                    <div class="border rounded p-3 bg-light"
                         style="white-space: pre-wrap;">{{ $ticket->description }}</div>
                </div>

                @if ($ticket->admin_comment)
                    <div class="form-group">
                        <label class="font-weight-bold">Комментарий
                            администратора</label>
                        <div class="border rounded p-3 bg-light"
                             style="white-space: pre-wrap;">{{ $ticket->admin_comment }}</div>
                        @if ($ticket->admin)
                            <small class="text-muted d-block mt-1">
                                Ответил: {{ $ticket->admin->name }}
                                @if ($ticket->admin->role)
                                    ({{ \App\Services\UserService::translateRoleName($ticket->admin->role->name) }}
                                    )
                                @endif
                                @if ($ticket->closed_at)
                                    , {{ $ticket->closed_at->format('d.m.Y H:i') }}
                                @endif
                            </small>
                        @endif
                    </div>
                @endif

                @if ($ticket->screenshot)
                    <div class="form-group">
                        <label class="font-weight-bold">Скриншот</label>
                        <div>
                            @if (\Illuminate\Support\Facades\Storage::disk('public')->exists($ticket->screenshot))
                                <a href="{{ asset('storage/'.$ticket->screenshot) }}"
                                   target="_blank">
                                    <img
                                        src="{{ asset('storage/'.$ticket->screenshot) }}"
                                        alt="Скриншот"
                                        style="max-width: 100%; max-height: 400px; height: auto;">
                                </a>
                            @else
                                <span class="text-muted">Файл скриншота недоступен</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@stop
