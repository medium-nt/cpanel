@extends('layouts.app')

@section('subtitle', 'Движения материалов')
@section('content_header_title', 'Движения материалов')

@section('content_body')
    <div class="col-md-12">

        {{-- Фильтры --}}
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 mb-1">
                        <select name="type_movement" id="type_movement"
                                class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">Все типы</option>
                            @foreach($types as $key => $type)
                                <option value="{{ $key }}"
                                        @if(isset($filters['type_movement']) && $filters['type_movement'] == $key) selected @endif>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-1">
                        <select name="status" id="status" class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">Все статусы</option>
                            @foreach($statuses as $key => $status)
                                <option value="{{ $key }}"
                                        @if(isset($filters['status']) && $filters['status'] == $key) selected @endif>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-1">
                        <select name="seamstress_id" id="seamstress_id"
                                class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">Все швеи</option>
                            @foreach($seamstresses as $id => $name)
                                <option value="{{ $id }}"
                                        @if(isset($filters['seamstress_id']) && $filters['seamstress_id'] == $id) selected @endif>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-1">
                        <select name="cutter_id" id="cutter_id"
                                class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">Все закройщики</option>
                            @foreach($cutters as $id => $name)
                                <option value="{{ $id }}"
                                        @if(isset($filters['cutter_id']) && $filters['cutter_id'] == $id) selected @endif>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-1">
                        <select name="material_id" id="material_id"
                                class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">Все материалы</option>
                            @foreach($materials as $id => $title)
                                <option value="{{ $id }}"
                                        @if(isset($filters['material_id']) && $filters['material_id'] == $id) selected @endif>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-1">
                        <select name="supplier_id" id="supplier_id"
                                class="form-control"
                                onchange="updatePageWithQueryParam(this)">
                            <option value="">Все поставщики</option>
                            @foreach($suppliers as $id => $title)
                                <option value="{{ $id }}"
                                        @if(isset($filters['supplier_id']) && $filters['supplier_id'] == $id) selected @endif>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-1">
                        <input type="date" name="date_from" id="date_from"
                               class="form-control"
                               value="{{ $filters['date_from'] ?? '' }}"
                               placeholder="Дата от"
                               onchange="updatePageWithQueryParam(this)">
                    </div>

                    <div class="col-md-2 mb-1">
                        <input type="date" name="date_to" id="date_to"
                               class="form-control"
                               value="{{ $filters['date_to'] ?? '' }}"
                               placeholder="Дата до"
                               onchange="updatePageWithQueryParam(this)">
                    </div>

                    <div class="col-md-2 mb-1">
                        <a href="{{ route('material-movements.index') }}"
                           class="btn btn-secondary w-100">
                            <i class="fas fa-times"></i> Сбросить
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Таблица --}}
        <div class="card mt-2">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col" style="width: 50px;">#</th>
                            <th scope="col" style="width: 140px;">Дата</th>
                            <th scope="col">Тип / Статус</th>
                            <th scope="col">Материал</th>
                            <th scope="col" style="width: 80px;">Рулон</th>
                            <th scope="col" style="width: 130px;">Кол-во /
                                Цена
                            </th>
                            <th scope="col">Исполнитель</th>
                            <th scope="col">Поставщик</th>
                            <th scope="col">Комментарий</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($movements as $order)
                            @php
                                $materials = $order->movementMaterials;
                                if (isset($filters['material_id'])) {
                                    $materials = $materials->where('material_id', $filters['material_id']);
                                }
                                if (isset($filters['roll_id'])) {
                                    $materials = $materials->where('roll_id', $filters['roll_id']);
                                }
                                $material = $materials->first();
                            @endphp
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div>{{ $order->type_movement_name }}</div>
                                    <span
                                        class="badge {{ $order->status_color }}">{{ $order->status_name }}</span>
                                </td>
                                <td>
                                    @if($material && $material->material)
                                        <b>{{ $material->material->title }}</b>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($material && $material->roll_id)
                                        <a href="{{ route('rolls.printRoll', ['roll' => $material->roll_id]) }}"
                                           class="btn
                                           @if($material->roll->is_printed) btn-outline-secondary @else btn-danger @endif
                                           btn-xs py-0" target="_blank">
                                            <i class="fas fa-barcode"></i>
                                        </a>
                                        <small
                                            class="d-block text-muted">#{{ $material->roll_id }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($material)
                                        @if($material->material)
                                            <div>{{ $material->quantity }} {{ $material->material->unit }}</div>
                                        @else
                                            <div>{{ $material->quantity }}</div>
                                        @endif
                                        @if($material->price > 0)
                                            <div class="text-muted">
                                                × {{ number_format($material->price, 2, ',', ' ') }}
                                                ₽
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->seamstress)
                                        <div>
                                            <i class="fas fa-map-pin mr-2"></i> {{ $order->seamstress->name }}
                                        </div>
                                    @elseif($order->cutter)
                                        <div>
                                            <i class="fas fa-cut mr-2"></i> {{ $order->cutter->name }}
                                        </div>
                                    @elseif($order->user)
                                        <div>
                                            <i class="fas fa-user mr-2"></i> {{ $order->user->name }}
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->supplier)
                                        {{ $order->supplier->title }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->comment)
                                        {{ Str::limit($order->comment, 50) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <p class="text-muted mb-0">Движения
                                        материалов не найдены</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Пагинация --}}
                @if($movements->hasPages())
                    <x-pagination-component :collection="$movements"/>
                @endif
            </div>
        </div>

        {{-- Информация о результатах --}}
        <div class="mt-2">
            <small class="text-muted">
                Показано {{ $movements->firstItem() ?? 0 }}
                - {{ $movements->lastItem() ?? 0 }}
                из {{ $movements->total() }} записей
            </small>
        </div>

    </div>
@stop

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
