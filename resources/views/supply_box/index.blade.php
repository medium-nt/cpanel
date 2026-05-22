@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <a href="{{ route('marketplace_supplies.show', ['marketplace_supply' => $supply]) }}"
                   class="btn btn-link mb-3">
                    &larr; Назад к поставке
                </a>

                <h4>Нераспределённых заказов: {{ $freeOrdersCount }}</h4>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Короба ({{ $boxes->count() }})</h3>
            </div>
            <div class="card-body">
                @if($freeOrdersCount > 0 && (auth()->user()->isAdmin() || auth()->user()->isStorekeeper()))
                    <form
                        action="{{ route('supply_boxes.store', ['marketplace_supply' => $supply]) }}"
                        method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary mb-3">
                            Добавить короб
                        </button>
                    </form>
                @endif
                @if($boxes->isNotEmpty())
                    @if($freeOrdersCount === 0 && $supply->status == 13 && $boxes->every(fn($box) => $box->closed_at) && (auth()->user()->isAdmin() || auth()->user()->isStorekeeper()))
                        <form
                            action="{{ route('supply_boxes.mark_assembled', ['marketplace_supply' => $supply]) }}"
                            method="POST" class="d-inline">
                            @csrf
                            <button type="submit"
                                    class="btn btn-success mb-3"
                                    onclick="return confirm('Подтвердите: все заказы распределены и короба закрыты. Поставка собрана?')">
                                Поставка собрана
                            </button>
                        </form>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-dark">
                            <tr>
                                <th>Номер короба</th>
                                <th>Статус</th>
                                <th>Заказов</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($boxes as $box)
                                <tr>
                                    <td>{{ $box->number }}</td>
                                    <td>
                                        @if($box->closed_at)
                                            <span class="badge badge-secondary">Закрыт</span>
                                        @else
                                            <span
                                                class="badge badge-success">Открыт</span>
                                        @endif
                                    </td>
                                    <td>{{ $box->orders_count }}</td>
                                    <td>
                                        <a href="{{ route('supply_boxes.show', ['marketplace_supply' => $supply, 'box' => $box]) }}"
                                           class="btn btn-primary btn-sm mr-1">
                                            Открыть
                                        </a>
                                        @if($box->orders_count == 0 && (auth()->user()->isAdmin() || auth()->user()->isStorekeeper()))
                                            <form
                                                action="{{ route('supply_boxes.destroy', ['marketplace_supply' => $supply, 'box' => $box]) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Удалить пустой короб?')">
                                                    Удалить
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mt-3">Короба ещё не созданы.</p>
                @endif
            </div>
        </div>
    </div>
@stop
