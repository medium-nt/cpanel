@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                <a href="{{ route('product_stickers.create') }}"
                   class="btn btn-primary mr-3 mb-3">Добавить стикер</a>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Название</th>
                            <th scope="col">Цвет</th>
                            <th scope="col">Вид принта</th>
                            <th scope="col">Материал</th>
                            <th scope="col">Страна</th>
                            <th scope="col">Тип крепления</th>
                            <th scope="col"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($stickers as $sticker)
                            <tr>
                                <td>{{ $sticker->id }}</td>
                                <td>{{ $sticker->title }}</td>
                                <td>{{ $sticker->color ?? '-' }}</td>
                                <td>{{ $sticker->print_type ?? '-' }}</td>
                                <td>{{ $sticker->material ?? '-' }}</td>
                                <td>{{ $sticker->country ?? '-' }}</td>
                                <td>{{ $sticker->fastening_type ?? '-' }}</td>

                                <td style="width: 100px">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('product_stickers.edit', ['productSticker' => $sticker->id]) }}"
                                           class="btn btn-primary mr-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form
                                            action="{{ route('product_stickers.destroy', ['productSticker' => $sticker->id]) }}"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-danger"
                                                    onclick="return confirm('Вы уверены что хотите удалить данный стикер?')">
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

            </div>
        </div>
    </div>
@stop
