@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="row">
        @if(auth()->user()->role->name != 'seamstress')
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('movements_defect_to_supplier.create') }}"
                       class="btn btn-primary mr-3">
                        Добавить возврат поставщику
                    </a>

                    <a href="{{ route('write_off_remnants.create') }}" class="btn btn-primary mr-3">
                        Списать остатки
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-dark">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Материал</th>
                                <th scope="col">Брак</th>
                                <th scope="col">Остатки</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($materials as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item['material']->title }}</td>
                                    <td>{{ $item['quantity'] }}</td>
                                    <td>{{ $item['remnants'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
        @endif

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">

                    @if(auth()->user()->role->name == 'seamstress')
                    <div class="row">
                        <div class="form-group col-md-6">
                            <a href="{{ route('defect_materials.create', ['type_movement_id' => 4]) }}" class="btn btn-primary mr-3 mb-3">Добавить новый брак</a>
                            <a href="{{ route('defect_materials.create', ['type_movement_id' => 7]) }}" class="btn btn-primary mb-3">Добавить новый остаток</a>
                        </div>
                    </div>
                    @endif

                    <div class="row">
                        <div class="form-group col-md-3">
                            <select name="seamstress_id"
                                    id="seamstress_id"
                                    class="form-control"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected>Все</option>
                                @foreach($seamstresses as $seamstress)
                                    <option value="{{ $seamstress->id }}"
                                            @if(request('seamstress_id') == $seamstress->id) selected @endif
                                    >{{ $seamstress->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <select name="type_movement"
                                    id="type_movement"
                                    class="form-control"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected>Все</option>
                                <option value="4" @if(request()->get('type_movement') == 4) selected @endif>Брак</option>
                                <option value="7" @if(request()->get('type_movement') == 7) selected @endif>Остатки</option>
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <input type="date"
                                   name="date_start"
                                   id="date_start"
                                   class="form-control"
                                   onchange="updatePageWithQueryParam(this)"
                                   value="{{ request('date_start') }}">
                        </div>

                        <div class="form-group col-md-3">
                            <input type="date"
                                   name="date_end"
                                   id="date_end"
                                   class="form-control"
                                   onchange="updatePageWithQueryParam(this)"
                                   value="{{ request('date_end') }}">
                        </div>
                    </div>

                        <a href="{{ route('defect_materials.index', [
                            'status' => 0,
                            'type' => request('type'),
                            'date_start' => request('date_start'),
                            'date_end' => request('date_end'),
                        ]) }}"
                           class="btn btn-link">Новые заказы</a>

                        <a href="{{ route('defect_materials.index', [
                            'status' => 3,
                            'type' => request('type'),
                            'date_start' => request('date_start'),
                            'date_end' => request('date_end'),
                        ]) }}"
                           class="btn btn-link">Завершенные</a>

                        <a href="{{ route('defect_materials.index', [
                            'status' => -1,
                            'type' => request('type'),
                            'date_start' => request('date_start'),
                            'date_end' => request('date_end'),
                        ]) }}"
                           class="btn btn-link">Отказанные</a>
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
                                                <b>{{ $material->material->title }}</b> - {{ $material->quantity }} {{ $material->material->unit }}
                                                <br>
                                            </li>
                                        @endforeach
                                        <div class="mt-2">
                                            Швея: <b>{{ $order->seamstress->name }}</b>
                                        </div>
                                        <div class="my-2">
                                            Комментарий: <b>{{ $order->comment }}</b>
                                        </div>

                                        @switch($order->status)
                                            @case(0)
                                                @if(auth()->user()->role->name == 'admin')
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('defect_materials.approve_reject', ['order' => $order->id]) }}"
                                                           class="btn btn-warning mr-1">
                                                            <i class="fas fa-check"></i> Согласовать
                                                        </a>
                                                    </div>
                                                @endif
                                                @break
                                            @case(1)
                                                @if(auth()->user()->role->name == 'storekeeper')
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('defect_materials.pick_up', ['order' => $order->id]) }}"
                                                           class="btn btn-warning mr-1">
                                                            <i class="fas fa-dolly"></i> Принять
                                                        </a>
                                                    </div>
                                                @endif
                                                @break
                                        @endswitch
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
                                <th scope="col"></th>
                                <th scope="col">Материалы</th>
                                <th scope="col">Статус</th>
                                <th scope="col">Тип</th>
                                <th scope="col">Швея</th>
                                <th scope="col">Комментарий</th>
                                <th scope="col">Дата</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td style="width: 70px">
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

                                                @if(auth()->user()->role->name == 'admin')
                                                    <form action="{{ route('defect_materials.delete', ['order' => $order->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger mr-1"
                                                                title="Удалить заявку"
                                                                onclick="return confirm('Вы уверены что хотите удалить заявку на брак?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        @foreach($order->movementMaterials as $material)
                                            <b>{{ $material->material->title }}</b> - {{ $material->quantity }} {{ $material->material->unit }} <br>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge {{ $order->status_color }}"> {{ $order->status_name }}</span>
                                    </td>
                                    <td>{{ $order->type_movement_name }}</td>
                                    <td>{{ $order->seamstress->name ?? '' }}</td>
                                    <td>{{ $order->comment }}</td>
                                    <td>{{ $order->created_date }}</td>

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
    </div>
@stop

{{-- Push extra CSS --}}

@push('css')
    <link href="{{ asset('css/desktop_or_smartphone_card_style.css') }}" rel="stylesheet"/>
@endpush

{{-- Push extra scripts --}}

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
