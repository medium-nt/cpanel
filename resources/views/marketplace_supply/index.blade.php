@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="dropdown mb-3 mr-3">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="supplyDropdown"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Создать поставку
                        </button>
                        <div class="dropdown-menu" aria-labelledby="supplyDropdown">
                            <a class="dropdown-item" href="{{ route('marketplace_supplies.create', ['marketplace_id' => 1]) }}">OZON</a>
                            <a class="dropdown-item" href="{{ route('marketplace_supplies.create', ['marketplace_id' => 2]) }}">WB</a>
                        </div>
                    </div>

                    <a href="{{ route('marketplace_supplies.index', ['status' => 0, 'marketplace_id' => request('marketplace_id')]) }}"
                       class="btn btn-link mr-3 mb-3">Открытые поставки</a>

                    <a href="{{ route('marketplace_supplies.index', ['status' => 1, 'marketplace_id' => request('marketplace_id')]) }}"
                       class="btn btn-link mr-3 mb-3">Выполненные</a>

                    <div class="form-group col-md-2">
                        <select name="marketplace_id"
                                id="marketplace_id"
                                class="form-control"
                                onchange="updatePageWithQueryParam(this)"
                                required>
                            <option value="" selected>---</option>
                            <option value="1" @if(request()->get('marketplace_id') == 1) selected @endif>OZON</option>
                            <option value="2" @if(request()->get('marketplace_id') == 2) selected @endif>WB</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row only-on-smartphone">
            @foreach ($marketplace_supplies as $marketplace_supply)
                <div class="col-md-4">
                    <div class="card">
                        <div class="position-relative">
                            <div class="ribbon-wrapper ribbon-lg">
                                <div class="ribbon bg-gradient-gray-dark text-lg">
                                    <img style="width: 80px;"
                                         src="{{ asset($marketplace_supply->marketplace_name) }}"
                                         alt="{{ $marketplace_supply->marketplace_name }}">
                                </div>
                            </div>
                            <div class="card-body">
                                <b>{{ $marketplace_supply->supply_id }} </b>
                                <span class="mx-1 badge {{ $marketplace_supply->status_color }}"> {{ $marketplace_supply->status_name }}</span>

                                <div class="my-3">

                                    <div class="mt-2">
                                        <small class="mr-2">
                                            Создан: <b> {{ now()->parse($marketplace_supply->created_at)->format('d/m/Y H:i') }}</b>
                                        </small>
                                        <badge class="badge
                                        @if($marketplace_supply->created_at->addHours(41)->isPast()) badge-hot
                                        @elseif($marketplace_supply->created_at->addHours(21)->isPast()) badge-old
                                        @else badge-new
                                        @endif">
                                            {{ $marketplace_supply->created_at->diffForHumans(['parts' => 2]) }}
                                        </badge>
                                    </div>
                                </div>

                                @if(auth()->user()->role->name == 'admin')
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply->id]) }}"
                                           class="btn btn-primary mr-3">
                                            <i class="fas fa-edit"></i> Редактировать
                                        </a>

{{--                                        <form method="POST"--}}
{{--                                              action="{{ route('marketplace_supplies.destroy', ['marketplace_supply' => $marketplace_supply->id]) }}">--}}
{{--                                            @csrf--}}
{{--                                            @method('DELETE')--}}
{{--                                            <button type="submit" class="btn btn-danger mr-3"--}}
{{--                                                    onclick="return confirm('Вы уверены что хотите удалить данный заказ из системы?')">--}}
{{--                                                <i class="fas fa-trash"></i> Удалить--}}
{{--                                            </button>--}}
{{--                                        </form>--}}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <x-pagination-component :collection="$marketplace_supplies" />
        </div>

        <div class="card only-on-desktop">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Номер поставки</th>
                            <th scope="col">Маркетплейс</th>
                            <th scope="col">Создан</th>
                            <th scope="col">Выполнен</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($marketplace_supplies as $marketplace_supply)
                            <tr>
                                <td>{{ $marketplace_supply->id }}</td>
                                <td>
                                    @if($marketplace_supply->status == 0)
                                        <span class="badge bg-secondary"> Открытая </span>
                                    @else
                                        <span class="badge bg-success"> Закрытая </span>
                                    @endif
                                </td>
                                <td>{{ $marketplace_supply->supply_id }}</td>
                                <td>
                                    <img style="width: 80px;"
                                         src="{{ asset($marketplace_supply->marketplace_name) }}"
                                         alt="{{ $marketplace_supply->marketplace_name }}">
                                </td>

                                <td>
                                    <span class="mr-2">{{ now()->parse($marketplace_supply->created_at)->format('d/m/Y H:i') }}</span>
                                    <badge class="badge
                                    @if($marketplace_supply->created_at->addHours(41)->isPast()) badge-hot
                                    @elseif($marketplace_supply->created_at->addHours(21)->isPast()) badge-old
                                    @else badge-new
                                    @endif">
                                        {{ $marketplace_supply->created_at->diffForHumans(['parts' => 2]) }}
                                    </badge><br>
                                </td>
                                <td>{{ is_null($marketplace_supply->completed_at) ? '' : now()->parse($marketplace_supply->completed_at)->format('d/m/Y H:i') }}</td>

                                <td style="width: 100px">
                                    @if(auth()->user()->role->name == 'admin' || auth()->user()->role->name == 'storekeeper')
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('marketplace_supplies.show', ['marketplace_supply' => $marketplace_supply->id]) }}"
                                               class="btn btn-primary mr-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('marketplace_supplies.destroy', ['marketplace_supply' => $marketplace_supply]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger"
                                                        onclick="return confirm('Вы уверены что хотите удалить данную поставку из системы?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                <x-pagination-component :collection="$marketplace_supplies" />

            </div>
        </div>
    </div>
@stop

@push('css')
    <link href="{{ asset('css/desktop_or_smartphone_card_style.css') }}" rel="stylesheet"/>
    <link href="{{ asset('css/badges.css') }}" rel="stylesheet"/>
@endpush

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
