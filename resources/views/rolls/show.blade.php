@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">

        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <a href="{{ $backUrl }}"
                       class="btn btn-default mr-3">Назад</a>

                    @if(auth()->user()->isAdmin() && $canDelete)
                        <form
                            action="{{ route('rolls.destroy', ['roll' => $roll->id]) }}"
                            method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fa fa-trash"></i>
                            </button>
                        </form>
                    @endif

                    @if(auth()->user()->isAdmin() && $roll->status === \App\Models\Roll::STATUS_IN_WORKSHOP)
                        <form
                            action="{{ route('rolls.returnToStorage', ['roll' => $roll->id]) }}"
                            method="POST">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-warning mr-3"
                                    onclick="return confirm('Вернуть рулон #{{ $roll->roll_code }} на склад?')">
                                <i class="fa fa-undo mr-1"></i> Вернуть на склад
                            </button>
                        </form>

                        @if($roll->current_quantity > 0)
                            <button type="button" class="btn btn-danger"
                                    data-toggle="modal"
                                    data-target="#writeOffModal">
                                <i class="fa fa-minus mr-1"></i> Списать метраж
                            </button>
                        @endif
                    @endif
                </div>

                <table class="table table-bordered">
                    <tr>
                        <th>Материал</th>
                        <td>{{ $roll->material->title }}</td>
                    </tr>
                    <tr>
                        <th>Статус</th>
                        <td><span
                                class="badge {{ $roll->status_color }}"> {{ $roll->status_name }}</span>
                        </td>
                    </tr>
                    @if($roll->shift)
                        <tr>
                            <th>Смена</th>
                            <td>{{ $roll->shift->name }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th>Начальное количество</th>
                        <td>{{ $roll->initial_quantity }} {{ $roll->material->unit }}</td>
                    </tr>
                    <tr>
                        <th>Текущий остаток (по системе)</th>
                        <td class="font-weight-bold">{{ $roll->current_quantity }} {{ $roll->material->unit }}</td>
                    </tr>
                    @if($roll->shortage_quantity > 0 || $roll->status === \App\Models\Roll::STATUS_COMPLETED)
                        <tr class="table-warning">
                            <th>Недостача</th>
                            <td class="font-weight-bold text-danger">{{ $roll->shortage_quantity }} {{ $roll->material->unit }}</td>
                        </tr>
                    @endif
                    @if($roll->completed_at)
                        <tr>
                            <th>Дата завершения</th>
                            <td>{{ \Carbon\Carbon::parse($roll->completed_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if($roll->completedBy)
                            <tr>
                                <th>Закрыл(а)</th>
                                <td>{{ $roll->completedBy->name }}</td>
                            </tr>
                        @endif
                    @endif
                </table>
            </div>
        </div>

        @if(auth()->user()->isAdmin() && $roll->status === \App\Models\Roll::STATUS_IN_WORKSHOP && $roll->current_quantity > 0)
            <div class="modal fade" id="writeOffModal" tabindex="-1"
                 role="dialog">
                <div class="modal-dialog" role="document">
                    <form
                        action="{{ route('rolls.writeOff', ['roll' => $roll->id]) }}"
                        method="POST">
                        @csrf
                        <input type="hidden" name="back_url"
                               value="{{ $backUrl }}">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    Ручное списание метража
                                </h5>
                                <button type="button" class="close"
                                        data-dismiss="modal"
                                        aria-label="Закрыть">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted mb-3">
                                    Доступно:
                                    <strong>{{ $roll->current_quantity }} {{ $roll->material->unit }}</strong>
                                </p>
                                <div class="form-group">
                                    <label
                                        for="writeoff-quantity">Количество</label>
                                    <input type="number"
                                           name="quantity"
                                           id="writeoff-quantity"
                                           class="form-control {{ $errors->has('quantity') ? 'is-invalid' : '' }}"
                                           step="0.001"
                                           min="0.001"
                                           max="{{ $roll->current_quantity }}"
                                           value="{{ old('quantity') }}"
                                           required>
                                    @error('quantity')
                                    <span
                                        class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="writeoff-comment">Комментарий
                                        (причина)</label>
                                    <textarea name="comment"
                                              id="writeoff-comment"
                                              class="form-control {{ $errors->has('comment') ? 'is-invalid' : '' }}"
                                              rows="2"
                                              maxlength="1000">{{ old('comment') }}</textarea>
                                    @error('comment')
                                    <span
                                        class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default"
                                        data-dismiss="modal">Отмена
                                </button>
                                <button type="submit" class="btn btn-danger">
                                    Списать
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if($errors->has('quantity') || $errors->has('comment'))
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        $('#writeOffModal').modal('show');
                    });
                </script>
            @endif
        @endif

        <div class="card only-on-desktop">
            <div class="card-header">
                <h3 class="card-title">История движений</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Дата</th>
                            <th scope="col">Кол-во</th>
                            <th scope="col">На что</th>
                            <th scope="col">Тип движения</th>
                            <th scope="col">Сотрудник</th>
                            <th scope="col">Комментарий</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($roll->movementMaterialsNotFromSuppler as $movementMaterial)
                            @php
                                $order = $movementMaterial->order;
                                $type = $order?->type_movement;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $order?->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $movementMaterial['quantity'] }}</td>
                                <td>
                                    @if($type == 3 && $order?->marketplaceOrder)
                                        Заказ {{ $order->marketplaceOrder->order_id }}
                                    @else
                                        {{ \App\Models\TypeMovement::TYPES[$type] ?? '—' }}
                                    @endif
                                </td>
                                <td>{{ $order?->type_movement_name ?? '—' }}</td>
                                <td>
                                    @php
                                        $employees = array_filter([
                                            $order?->seamstress?->name,
                                            $order?->cutter?->name,
                                            $order?->user?->name,
                                        ]);
                                    @endphp
                                    {{ $employees ? implode(', ', $employees) : '—' }}
                                </td>
                                <td>{{ $order?->comment }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
@stop
