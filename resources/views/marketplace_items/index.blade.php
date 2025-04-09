@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('marketplace_items.create') }}" class="btn btn-primary mr-3 mb-3">Добавить товар</a>

                <div class="row">
                    <div class="form-group col-md-3">
                        <select name="title" id="title" class="form-control" onchange="updatePageWithQueryParam(this)" required>
                            <option value="" selected>Все</option>
                            <option value="Бамбук" @if(request('title') == 'Бамбук') selected @endif>Бамбук</option>
                            <option value="Сетка" @if(request('title') == 'Сетка') selected @endif>Сетка</option>
                            <option value="Лен" @if(request('title') == 'Лен') selected @endif>Лен</option>
                            <option value="Вуаль" @if(request('title') == 'Вуаль') selected @endif>Вуаль</option>
                            <option value="Шифон" @if(request('title') == 'Шифон') selected @endif>Шифон</option>
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <select name="width" id="width" class="form-control" onchange="updatePageWithQueryParam(this)" required>
                            <option value="" selected>Все</option>
                            <option value="200" @if(request('width') == '200') selected @endif>200</option>
                            <option value="300" @if(request('width') == '300') selected @endif>300</option>
                            <option value="400" @if(request('width') == '400') selected @endif>400</option>
                            <option value="500" @if(request('width') == '500') selected @endif>500</option>
                            <option value="600" @if(request('width') == '600') selected @endif>600</option>
                            <option value="700" @if(request('width') == '700') selected @endif>700</option>
                            <option value="800" @if(request('width') == '800') selected @endif>800</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Название</th>
                            <th scope="col">SKU</th>
                            <th scope="col">Ширина</th>
                            <th scope="col">Высота</th>
                            <th scope="col">Маркетплейс</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->title }}</td>
                                <td>{{ $item->sku }}</td>
                                <td>{{ $item->width }}</td>
                                <td>{{ $item->height }}</td>
                                <td>{{ $item->marketplace_name }}</td>

                                <td style="width: 100px">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('marketplace_items.edit', ['marketplace_item' => $item->id]) }}" class="btn btn-primary mr-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('marketplace_items.destroy', ['marketplace_item' => $item->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                    onclick="return confirm('Вы уверены что хотите удалить данный товар из системы?')">
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
                <x-pagination-component :collection="$items" />

            </div>
        </div>
    </div>
@stop

@push('js')
    <script>
        function updatePageWithQueryParam(selectElement) {
            const paramName = selectElement.name;
            const paramValue = selectElement.value;

            const urlParams = new URLSearchParams(window.location.search);

            // Проверяем, есть ли уже параметр с таким именем, и удаляем его перед установкой нового значения
            urlParams.delete(paramName);

            // Добавляем новый параметр
            if (paramValue !== '' && paramValue !== 'all') {
                urlParams.append(paramName, paramValue);
            }

            window.location.assign(`${window.location.origin}${window.location.pathname}?${urlParams}`);
        }
    </script>
@endpush
