@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                @if(auth()->user()->role->name == 'seamstress')
                    <a href="{{ route('defect_materials.create') }}" class="btn btn-primary mr-3 mb-3">Добавить новый брак</a>
                @endif

                <a href="{{ route('defect_materials.index', ['status' => 0]) }}"
                   class="btn btn-link mr-3 mb-3">Новые заказы</a>

                <a href="{{ route('defect_materials.index', ['status' => 3]) }}"
                   class="btn btn-link mr-3 mb-3">Завершенные</a>

                <a href="{{ route('defect_materials.index', ['status' => -1]) }}"
                   class="btn btn-link mr-3 mb-3">Отказанные</a>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Материалы</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Швея</th>
                            <th scope="col">Комментарий</th>
                            <th scope="col">Дата</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @foreach($order->movementMaterials as $material)
                                        <b>{{ $material->material->title }}</b> - {{ $material->quantity }} {{ $material->material->unit }} <br>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="badge {{ $order->status_color }}"> {{ $order->status_name }}</span>
                                </td>
                                <td>{{ $order->seamstress->name ?? '' }}</td>
                                <td>{{ $order->comment }}</td>
                                <td>{{ $order->created_date }}</td>

                                <td style="width: 100px">
                                        @switch($order->status)
                                            @case(0)
                                                @if(auth()->user()->role->name == 'admin')
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('defect_materials.approve_reject', ['order' => $order->id]) }}"
                                                           class="btn btn-warning mr-1">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                                @break
                                            @case(1)
                                                @if(auth()->user()->role->name == 'storekeeper')
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('defect_materials.pick_up', ['order' => $order->id]) }}"
                                                       class="btn btn-warning mr-1">
                                                        <i class="fas fa-dolly"></i>
                                                    </a>
                                                </div>
                                                @endif
                                                @break
                                        @endswitch
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
