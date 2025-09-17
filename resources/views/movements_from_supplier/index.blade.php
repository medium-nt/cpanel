@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">

        <div class="card">
            <div class="card-body">
                <a href="{{ route('movements_from_supplier.create') }}" class="btn btn-primary mr-3">Добавить новое поступление</a>
            </div>
        </div>

        <div class="card only-on-desktop">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Материалы</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Комментарий</th>
                            <th scope="col">Дата</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td>
                                    @foreach($order->movementMaterials as $material)
                                        <b>{{ $material->material->title }}</b> - {{ $material->quantity }} {{ $material->material->unit }} <br>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="badge {{ $order->status_color }}"> {{ $order->status_name }}</span>
                                </td>
                                <td>{{ $order->comment }}</td>
                                <td>{{ now()->parse($order->created_at)->format('d/m/Y') }}</td>

                                <td style="width: 100px">
                                    @if(auth()->user()->isAdmin())
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('movements_from_supplier.edit', ['order' => $order->id]) }}"
                                           class="btn btn-primary mr-1">
                                            <i class="fas fa-edit"></i>
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

        <div class="row only-on-smartphone">
            @foreach ($orders as $order)
                <div class="col-md-4">
                    <div class="card">
                        <div class="position-relative">
                            <div class="card-body">
                                <b>№ {{ $order->id }} </b>
                                <span class="badge ml-1 {{ $order->status_color }}"> {{ $order->status_name }}</span>

                                <div class="my-3">
                                    @foreach($order->movementMaterials as $material)
                                    <li>
                                        <b>{{ $material->material->title }}</b> - {{ $material->quantity }} {{ $material->material->unit }}
                                    </li>
                                    @endforeach

                                    <div class="mt-3">
                                        Комментарий: {{ $order->comment }}
                                    </div>

                                    <small class="mr-2">
                                        Создан: <b> {{ now()->parse($order->created_at)->format('d/m/Y') }}</b>
                                    </small>
                                </div>

                                @if(auth()->user()->isAdmin())
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('movements_from_supplier.edit', ['order' => $order->id]) }}"
                                           class="btn btn-primary mr-1">
                                            <i class="fas fa-edit"></i> Редактировать
                                        </a>
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <x-pagination-component :collection="$orders" />
        </div>
    </div>
@stop

@push('css')
    <link href="{{ asset('css/desktop_or_smartphone_card_style.css') }}" rel="stylesheet"/>
@endpush
