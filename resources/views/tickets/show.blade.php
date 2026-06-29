@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-10">
        <div class="card">
            <div class="card-body">
                <div
                    class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                    <div>
                        <h4 class="mb-1">Тикет #{{ $ticket->id }}</h4>
                        <span
                            class="badge {{ \App\Models\Ticket::BADGE_COLORS[$ticket->status] ?? 'badge-secondary' }}">
                            {{ \App\Models\Ticket::STATUSES[$ticket->status] ?? $ticket->status }}
                        </span>
                    </div>
                    @if ($ticket->status === \App\Models\Ticket::STATUS_NEW)
                        @can('close', $ticket)
                            <div class="btn-group">
                                <form
                                    action="{{ route('tickets.close', $ticket) }}"
                                    method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            class="btn btn-success mr-2"
                                            onclick="return confirm('Закрыть тикет #{{ $ticket->id }}?')">
                                        <i class="fas fa-check"></i> Закрыть
                                    </button>
                                </form>
                                <form
                                    action="{{ route('tickets.delete', $ticket) }}"
                                    method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            class="btn btn-secondary"
                                            onclick="return confirm('Переместить тикет #{{ $ticket->id }} в корзину?')">
                                        <i class="fas fa-trash"></i> В корзину
                                    </button>
                                </form>
                            </div>
                        @endcan
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px">Автор</th>
                            <td>{{ $ticket->user?->name ?? '—' }}</td>
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

        <a href="{{ route('tickets.index') }}" class="btn btn-secondary mt-3">
            <i class="fas fa-arrow-left"></i> Назад к списку
        </a>
    </div>
@stop
