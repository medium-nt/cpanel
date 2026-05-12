@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('supply_boxes.index', ['marketplace_supply' => $supply]) }}"
                   class="btn btn-link mb-3">
                    &larr; Назад к коробам
                </a>

                <h4>
                    {{ $box->number }}
                    @if($box->closed_at)
                        <span class="badge badge-secondary ml-2">Закрыт</span>
                    @endif
                </h4>

                @if(!$box->closed_at && $box->orders->count() > 0)
                    <form
                        action="{{ route('supply_boxes.close_box', ['marketplace_supply' => $supply, 'box' => $box]) }}"
                        method="POST" class="mt-2 d-inline">
                        @csrf
                        <button type="submit"
                                class="btn btn-warning btn-sm"
                                onclick="return confirm('Закрыть короб? После закрытия добавление и удаление товаров будет невозможно.')">
                            Закрыть короб
                        </button>
                    </form>
                @endif

                @if($box->closed_at)
                    <a href="{{ route('supply_boxes.print_sticker', ['marketplace_supply' => $supply, 'box' => $box]) }}"
                       class="btn btn-primary btn-sm mt-2"
                       target="_blank">
                        Распечатать стикер
                    </a>
                @endif
            </div>
        </div>

        @livewire('box-order-scanner', ['box' => $box])
    </div>
@stop
