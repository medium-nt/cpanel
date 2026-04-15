@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <span class="mr-2">Товар #{{ $item->id }} </span>
                        <span
                            class="badge {{ $item->status_color }}">{{ $item->status_name }}</span>
                    </h3>
                    <a href="{{ route('marketplace_order_items.index') }}"
                       class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Назад к списку
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form id="itemActionForm">
                    @csrf

                    {{-- Кнопки действий --}}
                    @if(auth()->user()->isSeamstress() || auth()->user()->isCutter() || auth()->user()->isAdmin())
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card card-outline card-dark">
                                    <div class="card-header">
                                        <h3 class="card-title">Действия</h3>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="_method"
                                               value="PUT">
                                        <div class="btn-group" role="group">
                                            @switch($item->status)
                                                @case(4)
                                                    @if(auth()->user()->isSeamstress())
                                                        <button type="submit"
                                                                class="btn btn-success mr-2"
                                                                onclick="submitAction('{{ route('marketplace_order_items.labeling', ['marketplace_order_item' => $item->id]) }}', 'Вы уверены что заказ выполнен?')">
                                                            <i class="far fa-sticky-note mr-1"></i>
                                                            На стикеровку
                                                        </button>
                                                    @endif

                                                    <button type="submit"
                                                            class="btn btn-danger mr-2"
                                                            @if(auth()->user()->isAdmin())
                                                                onclick="submitAction('{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}', 'Вы уверены что хотите снять товар со швеи?')"
                                                            @elseif(auth()->user()->isSeamstress())
                                                                onclick="submitAction('{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}', 'Вы уверены что хотите отказаться от заказа? Вам будет начислен штраф')"
                                                        @endif
                                                    >
                                                        <i class="fas fa-times mr-1"></i>
                                                        Отменить
                                                    </button>

                                                    @if($bonus > 0 && auth()->user()->isSeamstress())
                                                        <span
                                                            class="badge border border-warning text-dark p-2"
                                                            style="font-size: 18px;">
                                                        <b>+ {{ $bonus * $item->item->width / 100 }}</b>
                                                        <i class="fas fa-star text-warning"></i>
                                                    </span>
                                                    @endif
                                                    @break
                                                @case(5)
                                                    @if(auth()->user()->isAdmin())
                                                        <button type="submit"
                                                                class="btn btn-danger"
                                                                onclick="submitAction('{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}', 'Вы уверены что хотите снять товар со стикеровки?')">
                                                            <i class="fas fa-times mr-1"></i>
                                                            Отменить
                                                        </button>
                                                    @endif
                                                    @break
                                                @case(7)
                                                    @if(auth()->user()->isCutter())
                                                        <button type="submit"
                                                                class="btn btn-success mr-2"
                                                                onclick="submitAction('{{ route('marketplace_order_items.completeCutting', ['marketplace_order_item' => $item->id]) }}', 'Вы уверены что заказ выполнен?')">
                                                            <i class="far fa-sticky-note mr-1"></i>
                                                            Сдать раскроенное
                                                        </button>
                                                    @endif

                                                    <button type="submit"
                                                            class="btn btn-danger mr-2"
                                                            @if(auth()->user()->isAdmin())
                                                                onclick="submitAction('{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}', 'Вы уверены что хотите снять товар с закроя?')"
                                                            @elseif(auth()->user()->isCutter())
                                                                onclick="submitAction('{{ route('marketplace_order_items.cancel', ['marketplace_order_item' => $item->id]) }}', 'Вы уверены что хотите отказаться от заказа? Вам будет начислен штраф')"
                                                        @endif
                                                    >
                                                        <i class="fas fa-times mr-1"></i>
                                                        Отменить
                                                    </button>

                                                    @if($bonus > 0 && auth()->user()->isCutter())
                                                        <span
                                                            class="badge border border-warning text-dark p-2"
                                                            style="font-size: 18px;">
                                                        <b>+ {{ $bonus * $item->item->width / 100 }}</b>
                                                        <i class="fas fa-star text-warning"></i>
                                                    </span>
                                                    @endif
                                                    @break
                                            @endswitch
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                <div class="row">
                    {{-- Информация о товаре --}}
                    <div class="col-md-4">
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h3 class="card-title">Информация о товаре</h3>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 200px">Название</th>
                                        <td>{{ $item->item->title }}</td>
                                    </tr>
                                    <tr>
                                        <th>Артикул</th>
                                        <td>{{ $item->item->article ?? '---' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Ширина</th>
                                        <td>{{ $item->item->width }} см</td>
                                    </tr>
                                    <tr>
                                        <th>Высота</th>
                                        <td>{{ $item->item->height }} см</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Информация о заказе --}}
                    <div class="col-md-4">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Заказ</h3>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Номер заказа</th>
                                        <td>{{ $item->marketplaceOrder->order_id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Маркетплейс</th>
                                        <td>
                                            <img style="width: 60px;"
                                                 src="{{ asset($item->marketplaceOrder->marketplace_name) }}"
                                                 alt="{{ $item->marketplaceOrder->marketplace_name }}">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Тип</th>
                                        <td>{{ $item->marketplaceOrder->fulfillment_type }}</td>
                                    </tr>
                                    <tr>
                                        <th>Кластер</th>
                                        <td>{{ $item->marketplaceOrder->cluster ?? '---' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Материалы --}}
                    <div class="col-md-4">
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h3 class="card-title">Материалы</h3>
                            </div>
                            <div class="card-body">
                                @php
                                    $consumptions = $item->item->consumption;
                                    $showRolls = auth()->user()->isCutter() || auth()->user()->isSeamstress();

                                    if (auth()->user()->isCutter()) {
                                        $consumptions = $consumptions->filter(fn($c) => $c->material->type_id == 1);
                                    }
                                @endphp
                                @if($consumptions->isNotEmpty())
                                    @foreach($consumptions as $c)
                                        <div class="border rounded p-2 mb-2">
                                            <div
                                                class="d-flex justify-content-between mb-1">
                                                <b>{{ $c->material->title }}</b>
                                                <span>{{ $c->quantity }} {{ $c->material->unit }}</span>
                                            </div>
                                            @if($showRolls)
                                                @php
                                                    $rolls = $c->material->rolls
                                                        ->where('status', \App\Models\Roll::STATUS_IN_WORKSHOP);
                                                @endphp
                                                <select
                                                    name="roll_id[{{ $c->material_id }}]"
                                                    class="form-control form-control-sm">
                                                    <option value="">Выберите
                                                        рулон
                                                    </option>
                                                    @foreach($rolls as $roll)
                                                        <option
                                                            value="{{ $roll->id }}">
                                                            Рулон
                                                            #{{ $roll->roll_code }}
                                                            (ост. {{ $roll->current_quantity }} {{ $c->material->unit }}
                                                            )
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted text-center">Материалы
                                        не указаны</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Сотрудники --}}
                    <div class="col-md-6">
                        <div class="card card-outline card-success">
                            <div class="card-header">
                                <h3 class="card-title">Сотрудники</h3>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    @if($item->cutter_id)
                                        <tr>
                                            <th style="width: 150px">Закройщик
                                            </th>
                                            <td>{{ $item->cutter?->name }}
                                                (ID: {{ $item->cutter_id }})
                                            </td>
                                        </tr>
                                    @endif
                                    @if($item->seamstress_id)
                                        <tr>
                                            <th>Швея</th>
                                            <td>{{ $item->seamstress?->name }}
                                                (ID: {{ $item->seamstress_id }})
                                            </td>
                                        </tr>
                                    @endif
                                    @if($item->otk_id)
                                        <tr>
                                            <th>Упаковщик</th>
                                            <td>{{ $item->otk?->name }}
                                                (ID: {{ $item->otk_id }})
                                            </td>
                                        </tr>
                                    @endif
                                    @if($item->repacker_id)
                                        <tr>
                                            <th>Переупаковщик</th>
                                            <td>{{ $item->repacker?->name }}
                                                (ID: {{ $item->repacker_id }})
                                            </td>
                                        </tr>
                                    @endif
                                    @if(!$item->cutter_id && !$item->seamstress_id && !$item->otk_id && !$item->repacker_id)
                                        <tr>
                                            <td colspan="2"
                                                class="text-center text-muted">
                                                Сотрудники не назначены
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Таймлайн --}}
                    <div class="col-md-6">
                        <div class="card card-outline card-warning">
                            <div class="card-header">
                                <h3 class="card-title">Таймлайн</h3>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    @php
                                        $events = collect();

                                        $events->push([
                                            'date' => $item->created_at,
                                            'label' => $item->created_at->format('d.m.Y H:i'),
                                            'label_color' => 'bg-info',
                                            'icon' => 'fas fa-plus',
                                            'icon_color' => 'bg-info',
                                            'title' => 'Заказ создан',
                                            'user' => null,
                                        ]);

                                        if ($item->cutting_completed_at) {
                                            $events->push([
                                                'date' => \Carbon\Carbon::parse($item->cutting_completed_at),
                                                'label' => \Carbon\Carbon::parse($item->cutting_completed_at)->format('d.m.Y H:i'),
                                                'label_color' => 'bg-warning',
                                                'icon' => 'fas fa-cut',
                                                'icon_color' => 'bg-warning',
                                                'title' => 'Раскрой завершен',
                                                'user' => $item->cutter,
                                            ]);
                                        }

                                        if ($item->packed_at) {
                                            $events->push([
                                                'date' => \Carbon\Carbon::parse($item->packed_at),
                                                'label' => \Carbon\Carbon::parse($item->packed_at)->format('d.m.Y H:i'),
                                                'label_color' => 'bg-primary',
                                                'icon' => 'fas fa-box',
                                                'icon_color' => 'bg-primary',
                                                'title' => 'Упакован',
                                                'user' => $item->otk,
                                            ]);
                                        }

                                        if ($item->repacked_at) {
                                            $events->push([
                                                'date' => \Carbon\Carbon::parse($item->repacked_at),
                                                'label' => \Carbon\Carbon::parse($item->repacked_at)->format('d.m.Y H:i'),
                                                'label_color' => 'bg-dark',
                                                'icon' => 'fas fa-box-open',
                                                'icon_color' => 'bg-dark',
                                                'title' => 'Переупакован',
                                                'user' => $item->repacker,
                                            ]);
                                        }

                                        if ($item->completed_at) {
                                            $events->push([
                                                'date' => \Carbon\Carbon::parse($item->completed_at),
                                                'label' => \Carbon\Carbon::parse($item->completed_at)->format('d.m.Y H:i'),
                                                'label_color' => 'bg-success',
                                                'icon' => 'fas fa-check',
                                                'icon_color' => 'bg-success',
                                                'title' => 'Заказ завершен',
                                                'user' => $item->otk,
                                            ]);
                                        }

                                        $events = $events->sortBy('date');
                                    @endphp

                                    @foreach($events as $i => $event)
                                        <div class="time-label">
                                            <span
                                                class="{{ $event['label_color'] }} py-0 px-2"
                                                style="font-size: 12px">{{ $event['label'] }}</span>
                                        </div>
                                        <div>
                                            <i class="{{ $event['icon'] }} {{ $event['icon_color'] }}"></i>
                                            <div class="timeline-item">
                                                <h3 class="timeline-header no-border">
                                                    {{ $event['title'] }}
                                                    @if($event['user'])
                                                        — {{ $event['user']->name }}
                                                        (ID: {{ $event['user']->id }}
                                                        )
                                                    @endif
                                                </h3>
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="time-label">
                                        <span class="bg-secondary py-0 px-2"
                                              style="font-size: 12px; min-width: 100px; display: inline-block; text-align: center;">сейчас</span>
                                    </div>
                                    <div>
                                        <i class="fas fa-map-pin bg-secondary"></i>
                                        <div class="timeline-item">
                                            <h3 class="timeline-header no-border">
                                                <span
                                                    class="badge {{ $item->status_color }}">{{ $item->status_name }}</span>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- История --}}
                @if($item->history->isNotEmpty())
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-outline card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title">История</h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>Дата</th>
                                            <th>Статус</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($item->history->sortByDesc('created_at') as $record)
                                            <tr>
                                                <td>{{ $record->created_at->format('d.m.Y H:i') }}</td>
                                                <td>
                                                    <span
                                                        class="badge {{ \App\Models\StatusMovement::BADGE_COLORS[$record->status] ?? 'badge-secondary' }}">
                                                        {{ \App\Models\StatusMovement::STATUSES[$record->status] ?? 'Неизвестно' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                </form>
            </div>
        </div>
    </div>
@stop

@push('css')
    <link href="{{ asset('css/badges.css') }}" rel="stylesheet"/>
@endpush

@push('js')
    <script>
        function submitAction(action, message) {
            if (!confirm(message)) {
                event.preventDefault();
                return;
            }
            var form = document.getElementById('itemActionForm');
            form.action = action;
            form.method = 'POST';
        }
    </script>
@endpush
