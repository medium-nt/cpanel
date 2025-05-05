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
                    @if(auth()->user()->role->name == 'seamstress')
                        <a href="{{ route('movements_to_workshop.create') }}" class="btn btn-primary mr-1">Новый заказ</a>
                    @endif

                    <a href="{{ route('movements_to_workshop.index') }}" class="btn btn-link">Активные</a>
                    <a href="{{ route('movements_to_workshop.index', ['status' => 'all']) }}" class="btn btn-link">Все заказы</a>
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
                                    <b>{{ now()->parse($order->created_at)->format('d/m/Y') }}</b>
                                </small>
                                <span class="mx-1 badge {{ $order->status_color }}">
                                    {{ $order->status_name }}
                                </span>

                                <div class="mt-3">
                                    @foreach($order->movementMaterials as $material)
                                        <li>
                                            <b>{{ $material->material->title }}</b> -
                                            {{ $material->ordered_quantity }} {{ $material->material->unit }} /
                                            <span style="@if($order->status == '2' && $material->quantity < $material->ordered_quantity) color: red; @endif">
                                            {{ $material->quantity }} {{ $material->material->unit }}
                                            </span>
                                        </li>
                                    @endforeach

                                    <div class="my-2">
                                        Комментарий: <b>{{ $order->comment }}</b>
                                    </div>

                                    @if( $order->status == '0' &&  $userRole == 'storekeeper')
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('movements_to_workshop.collect', ['order' => $order->id]) }}"
                                               class="btn btn-warning mr-1"
                                               title="Сформировать">
                                                <i class="fas fa-box-open"></i> Сформировать
                                            </a>
                                        </div>
                                    @elseif( $order->status == '2' && $userRole == 'seamstress')
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('movements_to_workshop.receive', ['order' => $order->id]) }}"
                                               class="btn btn-success mr-1"
                                               title="Принять">
                                                <i class="fas fa-vote-yea"></i> Принять
                                            </a>
                                        </div>
                                    @endif

                                    @if($userRole == 'admin' && $order->status == '0')
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('movements_to_workshop.destroy', ['order' => $order->id]) }}"
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
                            <th scope="col">Статус</th>
                            <th scope="col">Дата</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td style="white-space: nowrap;">
                                    @foreach($order->movementMaterials as $material)
                                        <b>{{ $material->material->title }}</b> -
                                        {{ $material->ordered_quantity }} {{ $material->material->unit }}
                                        / <span style="@if($order->status == '2' && $material->quantity < $material->ordered_quantity) color: red; @endif">
                                            {{ $material->quantity }} {{ $material->material->unit }}
                                        </span>
                                        <br>
                                    @endforeach
                                </td>
                                <td>{{ $order->comment }}</td>
                                <td><span class="badge {{ $order->status_color }}"> {{ $order->status_name }}</span></td>
                                <td>{{ now()->parse($order->created_at)->format('d/m/Y') }}</td>

                                <td style="width: 100px">
                                    @if( $order->status == '0' &&  $userRole == 'storekeeper')
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('movements_to_workshop.collect', ['order' => $order->id]) }}"
                                           class="btn btn-warning mr-1"
                                           title="Сформировать">
                                            <i class="fas fa-box-open"></i>
                                        </a>
                                    </div>
                                    @elseif( $order->status == '2' && $userRole == 'seamstress')
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('movements_to_workshop.receive', ['order' => $order->id]) }}"
                                           class="btn btn-success mr-1"
                                           title="Принять">
                                            <i class="fas fa-vote-yea"></i>
                                        </a>
                                    </div>
                                    @endif

                                    @if($userRole == 'admin' && $order->status == '0')
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('movements_to_workshop.destroy', ['order' => $order->id]) }}"
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
