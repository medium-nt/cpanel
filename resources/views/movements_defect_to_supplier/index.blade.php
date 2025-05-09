@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('movements_defect_to_supplier.create') }}" class="btn btn-primary mr-3 mb-3">Добавить новый возврат</a>

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
{{--                                    @if(auth()->user()->role->name == 'admin')--}}
{{--                                    <div class="btn-group" role="group">--}}
{{--                                        <a href="{{ route('movements_from_supplier.edit', ['order' => $order->id]) }}"--}}
{{--                                           class="btn btn-primary mr-1">--}}
{{--                                            <i class="fas fa-edit"></i>--}}
{{--                                        </a>--}}
{{--                                    </div>--}}
{{--                                    @endif--}}
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
