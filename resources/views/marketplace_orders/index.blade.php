@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('marketplace_orders.create') }}" class="btn btn-primary mr-3 mb-3">Добавить заказ</a>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Номер заказа</th>
                            <th scope="col">Маркетплейс</th>
                            <th scope="col">Товары</th>
                            <th scope="col">Создан</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $order->order_id }}</td>
                                <td>{{ $order->marketplace_name }}</td>
                                <td>
                                    @foreach($order->items as $item)
                                        <b>{{ $item->item->title }}</b> - {{ $item->quantity }} шт. <br>
                                    @endforeach
                                </td>
                                <td>{{ now()->parse($order->created_at)->format('d/m/Y H:i') }}</td>

                                <td style="width: 100px">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('marketplace_orders.edit', ['marketplace_order' => $order->id]) }}" class="btn btn-primary mr-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('marketplace_orders.destroy', ['marketplace_order' => $order->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                    onclick="return confirm('Вы уверены что хотите удалить данный заказ из системы?')">
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
                <x-pagination-component :collection="$orders" />

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
    {{--    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>--}}
@endpush
