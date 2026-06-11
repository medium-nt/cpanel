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

                @include('supply_box._supply_info', ['supply' => $supply])

                <h4>
                    {{ $box->number }}
                    @if($box->closed_at)
                        <span class="badge badge-secondary ml-2">Закрыт</span>
                    @endif
                </h4>
                @if($box->cargo_id)
                    <small class="text-muted">ID грузового
                        места: {{ $box->cargo_id }}</small><br>
                @endif

                @if(!$box->closed_at && $box->orders->count() > 0 && (auth()->user()->isAdmin() || auth()->user()->isStorekeeper()))
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

                @if($box->closed_at && (auth()->user()->isAdmin() || auth()->user()->isStorekeeper()))
                    <a href="{{ route('supply_boxes.print_sticker', ['marketplace_supply' => $supply, 'box' => $box]) }}"
                       class="btn btn-primary btn-sm mt-2"
                       target="_blank">
                        Распечатать стикер
                    </a>
                    @if($supply->marketplace_id === 1)
                        <a href="{{ route('supply_boxes.print_sticker', ['marketplace_supply' => $supply, 'box' => $box, 'regenerate' => 1]) }}"
                           class="btn btn-outline-dark btn-sm mt-2 ml-2"
                           target="_blank"
                           title="Перегенерировать стикер">
                            <i class="fas fa-sync-alt p-1"></i>
                        </a>
                    @endif
                @endif
            </div>
        </div>

        @livewire('box-order-scanner', ['box' => $box])
    </div>
@stop
