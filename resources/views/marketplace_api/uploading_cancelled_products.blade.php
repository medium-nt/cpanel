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
                            <th scope="col">#</th>
                            <th scope="col">Номер заказа</th>
                            <th scope="col">SKU</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($results as $result)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $result['order_id'] }}</td>
                                <td>{{ $result['status'] }}</td>
                            </tr>
                        @endforeach

                        @if(count($results) == 0)
                            <tr><td colspan="3" class=" text-center">Нет удаленных заказов</td></tr>
                        @endif
                        </tbody>
                    </table>

                    <a href="{{ route('marketplace_orders.index') }}" class="btn btn-primary">Вернуться в Заказы</a>
                </div>
            </div>
        </div>
    </div>
@stop
