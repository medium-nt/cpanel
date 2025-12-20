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
                    <div class="col-md-2 mb-1 mr-1">
                        <select class="form-control"
                                id="status"
                                name="status"
                                onchange="updatePageWithQueryParam(this)"
                                required>
                            <option value="all" selected>Все</option>
                            <option value="in_storage"
                                    @if(request()->get('status') == 'in_storage') selected @endif>
                                На складе
                            </option>
                            <option value="in_workshop"
                                    @if(request()->get('status') == 'in_workshop') selected @endif>
                                В цехе
                            </option>
                            <option value="completed"
                                    @if(request()->get('status') == 'completed') selected @endif>
                                Завершен
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-1 mr-1">
                        <form action="{{ route('rolls.index') }}" method="get">
                            <input type="text"
                                   name="search"
                                   id="search"
                                   class="form-control"
                                   placeholder="Поиск"
                                   value="{{ request('search') }}">
                            <input type="hidden" name="status" id="status"
                                   value="{{ request()->get('status') }}">
                        </form>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('rolls.index') }}"
                           class="btn btn-outline-secondary">Сбросить</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card only-on-desktop">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Статус</th>
                            <th scope="col">Штрихкод</th>
                            <th scope="col">Материал</th>
                            <th scope="col">шт./п.м.</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($rolls as $roll)
                            <tr>
                                <td>{{ $roll->id }}</td>
                                <td>
                                    <span
                                        class="badge {{ $roll->status_color }}"> {{ $roll->status_name }}</span>
                                </td>
                                <td>{{ $roll->roll_code }}</td>
                                <td>{{ $roll->material->title }}</td>
                                <td>{{ $roll->current_quantity }}
                                    из {{ $roll->initial_quantity }}</td>
                                <td>
                                    <a href="{{ route('rolls.show', $roll->id) }}"
                                       class="btn btn-primary mt-1">
                                        Подробнее
                                    </a>

                                    <a href="{{ route('rolls.printRoll', ['roll' => $roll->id]) }}"
                                       class="btn
                                        @if($roll->is_printed) btn-outline-secondary @else btn-danger @endif
                                        mx-1 mt-1">
                                        <i class="fas fa-barcode"></i>
                                    </a>

                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                <x-pagination-component :collection="$rolls"/>

            </div>
        </div>
    </div>
@stop

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
