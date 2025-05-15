@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">

                @if(auth()->user()->role->name == 'admin')
                    <a href="{{ route('movements_to_workshop.write_off') }}"
                       class="btn btn-primary mr-3 mb-3">Списание материала</a>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col" style="width: 75px;">#</th>
                            <th scope="col">Материал</th>
                            <th scope="col">Количество</th>
                            <th scope="col">Статус</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($materials as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item['material']->title }}</td>
                                <td>
                                    <span style="width: 100px; display: inline-block;">
                                        {{ $item['quantity'] }}
                                    </span>
                                </td>

                                <td>
                                    @if($item['quantity'] <= 100)
                                        <span class="badge badge-danger">
                                            очень мало
                                        </span>
                                    @elseif($item['quantity'] <= 300)
                                        <span class="badge badge-warning">
                                            достаточно
                                        </span>
                                    @else
                                        <span class="badge badge-success ml-3">
                                            более чем достаточно
                                        </span>
                                    @endif
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
