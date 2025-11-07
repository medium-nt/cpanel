@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('inventory.create') }}"
                   class="btn btn-primary mb-3 mr-3">
                    Создать инвентаризацию
                </a>

                @if(request('status') === 'closed')
                    <a href="{{ route('inventory.inventory_checks', ['status' => 'in_progress']) }}"
                       class="btn btn-link mb-3">
                        Открытые инвентаризации
                    </a>
                @endif

                @if(request('status') === 'in_progress' || request('status') === null)
                    <a href="{{ route('inventory.inventory_checks', ['status' => 'closed']) }}"
                       class="btn btn-link mb-3">
                        Закрытые инвентаризации
                    </a>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Прогресс</th>
                            <th scope="col">Комментарий</th>
                            <th scope="col">Создана</th>
                            <th scope="col">Завершена</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($inventories as $inventory)
                            <tr>
                                <td>
                                    {{ $inventory->id }}</td>
                                <td>
                                    <span
                                        style="width: 100px; display: inline-block;">
                                        @if ($inventory->status === 'in_progress')
                                            <span class="badge badge-warning">В процессе</span>
                                        @elseif ($inventory->status === 'closed')
                                            <span class="badge badge-danger">Закрыта</span>
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $total = $inventory->items->count();
                                        $found = $inventory->items->where('is_found', true)->count();
                                        $percentage = $total ? round($found / $total * 100) : 0;
                                    @endphp
                                    найдено: {{ $found }} из {{ $total }}
                                    <br>
                                    прогресс: {{ $percentage }}%
                                </td>
                                <td>
                                    {{ $inventory->comment }}
                                </td>
                                <td>
                                    {{ $inventory->created_date }}
                                </td>
                                <td>
                                    {{ $inventory->finished_date }}
                                </td>
                                <td style="width: 100px">
                                    <a href="{{ route('inventory.show', $inventory->id) }}"
                                       class="btn btn-primary">
                                        Посмотреть
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <x-pagination-component :collection="$inventories"/>

            </div>
        </div>
    </div>
@stop
