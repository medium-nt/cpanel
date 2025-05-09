@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Список не добавленных заказов</h3>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Номер заказа</th>
                            <th scope="col">SKU</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($results['not_found_skus'] as $order_id => $sku)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $order_id }}</td>
                                <td>{{ $sku }}</td>
                            </tr>
                        @endforeach

                        @if(count($results['not_found_skus']) == 0)
                            <tr><td colspan="3" class=" text-center">Все заказы успешно добавлены</td></tr>
                        @endif
                        </tbody>
                    </table>

                    <a href="{{ route('marketplace_orders.index') }}" class="btn btn-primary">Вернуться в Заказы</a>
                </div>
            </div>
        </div>

        @if($results['errors'] != [])
        <div class="card">
            <div class="card-header">
                <h3 class="card-title text-danger text-bold">Ошибки сохранения заказов</h3>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Номер заказа</th>
                            <th scope="col">SKU</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($results['errors'] as $order_id => $message)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $order_id }}</td>
                                <td>{{ $message }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
        @endif

    </div>
@stop
