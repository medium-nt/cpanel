@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-12">
        <div class="card">

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

                <div class="card-body">
                    @foreach($order->movementMaterials->filter(fn($m) => $m->roll_id !== null) as $item)
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Материал</label>
                            <input type="text"
                                   class="form-control"
                                   value="{{ $item->material->title }}"
                                   readonly>
                        </div>

                        <div class="col-md-2 form-group">
                            <label>Кол-во</label>
                            <input type="number"
                                   class="form-control"
                                   value="{{ $item->quantity }}"
                                   readonly>
                        </div>

                        <div class="col-md-4 form-group">
                            <label>Рулон</label>
                            <input type="text"
                                   class="form-control"
                                   value="{{ $item->roll->roll_code ?? '' }}"
                                   readonly>
                        </div>
                    </div>
                    @endforeach

                        @php
                            $scannedMaterials = $order->movementMaterials->filter(fn($m) => $m->roll_id !== null);
                        @endphp

                        <div class="font-weight-bold mt-2">
                            ИТОГО: {{ $scannedMaterials->count() }}
                            рул., {{ $scannedMaterials->sum('quantity') }} {{ $scannedMaterials->first()?->material->unit }}
                        </div>

                    @if($order->comment)
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label>Комментарий</label>
                                <textarea rows="3"
                                          class="form-control"
                                          readonly>{{ $order->comment }}</textarea>
                            </div>
                        </div>
                    @endif

                    @if(auth()->user()->isStorekeeper() || auth()->user()->isAdmin())
                        <div class="form-group">
                            <a href="{{ route('movements_to_workshop.index') }}"
                               class="btn btn-secondary mr-1">Назад</a>
                            <a href="{{ route('movements_to_workshop.print_sticker', ['order' => $order->id]) }}"
                               class="btn btn-outline-secondary"
                               target="_blank">
                                <i class="fas fa-sticky-note"></i> Печать
                                стикеров
                            </a>
                        </div>
                    @else
                        <form
                            action="{{ route('movements_to_workshop.save_receive', ['order' => $order->id]) }}"
                            method="POST">
                            @method('PUT')
                            @csrf
                            <div class="form-group">
                                <button class="btn btn-success">Принять
                                    поставку
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
        </div>
    </div>
@stop
