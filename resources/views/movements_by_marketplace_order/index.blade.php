@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col" style="width: 50px">#</th>
                            <th scope="col" style="white-space: nowrap;">Материалы</th>
                            <th scope="col">Комментарии</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Дата</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td style="white-space: nowrap;">
                                    @foreach($order->movementMaterials as $material)
                                        <b>{{ $material->material->title }}</b>
                                        {{ $material->quantity }} {{ $material->material->unit }}
                                        <br>
                                    @endforeach
                                </td>
                                <td>{{ $order->comment }}</td>
                                <td><span class="badge {{ $order->status_color }}"> {{ $order->status_name }}</span></td>
                                <td>{{ now()->parse($order->created_at)->format('d/m/Y') }}</td>

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
