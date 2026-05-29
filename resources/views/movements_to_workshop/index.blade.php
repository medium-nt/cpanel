@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    @if(auth()->user()->isSeamstress() || auth()->user()->isCutter() || auth()->user()->isOtk())
                        <a href="{{ route('movements_to_workshop.create') }}"
                           class="btn btn-primary mr-1 mb-1">Новый заказ</a>
                    @endif

                    <select class="form-control mr-2 mb-1"
                            style="width: auto; display: inline-block;"
                            name="status"
                            onchange="updateStatusFilter(this)">
                        <option value=""
                                @if(!request()->has('status')) selected @endif>
                            Активные
                        </option>
                        <option value="all"
                                @if(request('status') === 'all') selected @endif>
                            Все заказы
                        </option>
                    </select>

                    @if(auth()->user()->isAdmin() || auth()->user()->isStorekeeper())
                        <select class="form-control ml-2 mb-1"
                                style="width: auto; display: inline-block;"
                                name="shift_id"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">Все смены</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}"
                                        @if(request('shift_id') == $shift->id) selected @endif>
                                    {{ $shift->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        </div>

        <div class="row only-on-smartphone">
            @foreach ($orders as $order)
                <div class="col-md-4">
                    <div class="card">
                        <div class="position-relative">
                            <div class="card-body">
                                <small>
                                    <b>{{ now()->parse($order->created_at)->format('d/m/Y H:i') }}</b>
                                </small>
                                <span class="mx-1 badge {{ $order->status_color }}">
                                    {{ $order->status_name }}
                                </span>

                                @if($order->shift)
                                    <span
                                        class="mx-1 badge badge-info">{{ $order->shift->name }}</span>
                                @endif

                                <div class="mt-3">
                                    @foreach($order->movementMaterials as $material)
                                        <li>
                                            <b>{{ $material->material->title }}</b>
                                            @if($material->roll)
                                                — рулон:
                                                <b>{{ $material->roll->roll_code }}</b>
                                                ({{ $material->quantity }} {{ $material->material->unit }}
                                                )
                                            @endif
                                        </li>
                                    @endforeach

                                    <div class="my-2">
                                        Комментарий: <b>{{ $order->comment }}</b>
                                    </div>

                                    @if($order->user)
                                        <div style="font-size: 0.85em;">
                                            Отгрузил:
                                            <b>{{ $order->user->name }}</b>
                                        </div>
                                    @endif

                                    @if($order->seamstress || $order->cutter)
                                        <div style="font-size: 0.85em;">
                                            Запросил/Получил:
                                            <b>{{ $order->seamstress?->name ?? $order->cutter?->name ?? $order->otk?->name }}</b>
                                        </div>
                                    @endif

                                    @if($order->status == '3' && $order->completed_at)
                                        <div class="my-2" style="white-space: nowrap; font-size: 0.8em;">
                                            <b>Получено:</b>
                                            {{ now()->parse($order->completed_at)->format('d/m/Y H:i') }}
                                        </div>
                                    @endif

                                        @if( $order->status == '0' && (auth()->user()->isStorekeeper() || auth()->user()->isAdmin()))
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('movements_to_workshop.collect', ['order' => $order->id]) }}"
                                               class="btn btn-warning mr-1"
                                               title="Сформировать">
                                                <i class="fas fa-box-open"></i> Сформировать
                                            </a>
                                        </div>
                                        @elseif( $order->status == '2' && (auth()->user()->isCutter() || auth()->user()->isSeamstress() || auth()->user()->isOtk()))
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('movements_to_workshop.receive', ['order' => $order->id]) }}"
                                               class="btn btn-success mr-1"
                                               title="Принять">
                                                <i class="fas fa-vote-yea"></i> Принять
                                            </a>
                                        </div>
                                    @elseif( $order->status == '2' && (auth()->user()->isStorekeeper() || auth()->user()->isAdmin()))
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('movements_to_workshop.receive', ['order' => $order->id]) }}"
                                               class="btn btn-info mr-1"
                                               title="Просмотр">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    @endif

                                    @if(auth()->user()->isAdmin() && $order->status == '0')
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('movements_to_workshop.destroy', ['order' => $order->id]) }}"
                                               onclick="return confirm('Вы уверены, что хотите удалить заказ?')"
                                               class="btn btn-danger" title="Удалить">
                                                <i class="fas fa-trash-alt"></i> Удалить
                                            </a>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <x-pagination-component :collection="$orders" />
        </div>

        <div class="card only-on-desktop">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col" style="white-space: nowrap;">запрошено / отгружено</th>
                            <th scope="col">Комментарии</th>
                            <th scope="col">Статус </th>
                            <th scope="col">Смена</th>
                            <th scope="col">Отгрузил</th>
                            <th scope="col">Запросил/Получил</th>
                            <th scope="col">Заказ создан</th>
                            <th scope="col">Получено</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td style="white-space: nowrap;">
                                    @foreach($order->movementMaterials as $material)
                                        <b>{{ $material->material->title }}</b>
                                        @if($material->roll)
                                            — рулон:
                                            <b>{{ $material->roll->roll_code }}</b>
                                            ({{ $material->quantity }} {{ $material->material->unit }}
                                            )
                                        @endif
                                        <br>
                                    @endforeach
                                </td>
                                <td>{{ $order->comment }}</td>
                                <td><span class="badge {{ $order->status_color }}"> {{ $order->status_name }}</span></td>
                                <td>
                                    @if($order->shift)
                                        <span
                                            class="badge badge-info">{{ $order->shift->name }}</span>
                                    @endif
                                </td>
                                <td>{{ $order->user?->name }}</td>
                                <td>{{ $order->seamstress?->name ?? $order->cutter?->name ?? $order->otk?->name }}</td>
                                <td>{{ now()->parse($order->created_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($order->status == '3' && $order->completed_at)
                                        {{ now()->parse($order->completed_at)->format('d/m/Y H:i') }}
                                    @endif
                                </td>
                                <td style="width: 120px">
                                    @if( $order->status == '0' && (auth()->user()->isStorekeeper() || auth()->user()->isAdmin()))
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('movements_to_workshop.collect', ['order' => $order->id]) }}"
                                           class="btn btn-warning mr-1 mb-1"
                                           title="Сформировать">
                                            <i class="fas fa-box-open"></i>
                                        </a>
                                    </div>
                                    @elseif( $order->status == '2' && (auth()->user()->isCutter() || auth()->user()->isSeamstress() || auth()->user()->isOtk()))
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('movements_to_workshop.receive', ['order' => $order->id]) }}"
                                           class="btn btn-success mr-1"
                                           title="Принять">
                                            <i class="fas fa-vote-yea"></i>
                                        </a>
                                    </div>
                                    @elseif( $order->status == '2' && (auth()->user()->isStorekeeper() || auth()->user()->isAdmin()))
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('movements_to_workshop.receive', ['order' => $order->id]) }}"
                                               class="btn btn-info mr-1"
                                               title="Просмотр">
                                                <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                    @endif

                                    @if(auth()->user()->isAdmin() && $order->status == '0')
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('movements_to_workshop.destroy', ['order' => $order->id]) }}"
                                               onclick="return confirm('Вы уверены что хотите удалить?')"
                                                class="btn btn-danger" title="Удалить">
                                                    <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                <x-pagination-component :collection="$orders" />

            </div>
        </div>
    </div>
@stop

@push('css')
    <link href="{{ asset('css/desktop_or_smartphone_card_style.css') }}" rel="stylesheet"/>
@endpush

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
    <script>
        function updateStatusFilter(el) {
            const params = new URLSearchParams(window.location.search);
            params.delete('status');
            params.delete('page');
            if (el.value) {
                params.set('status', el.value);
            }
            window.location = window.location.pathname + '?' + params.toString();
        }
    </script>
@endpush
