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
                            @foreach($titleMaterials as $titleMaterial)
                                <option value="{{ $titleMaterial->title }}"
                                        @if(request('title') == $titleMaterial->title) selected @endif
                                >{{ $titleMaterial->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <select name="width" id="width" class="form-control" onchange="updatePageWithQueryParam(this)" required>
                            <option value="" selected>Все</option>
                            @foreach($widthMaterials as $widthMaterial)
                                <option value="{{ $widthMaterial->width }}"
                                        @if(request('width') == $widthMaterial->width) selected @endif
                                >{{ $widthMaterial->width }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Название</th>
                            <th scope="col">Ширина</th>
                            <th scope="col">Высота</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->title }}</td>
                                <td>{{ $item->width }}</td>
                                <td>{{ $item->height }}</td>

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
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
