<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="shortcut icon"
          href="{{ asset('vendor/adminlte/dist/img/crm_logo.png') }}">
    <title>МЕГАТЮЛЬ | {{ $title }}</title>

    <link rel="stylesheet"
          href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet"
          href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('css/kiosk.css') }}">

    <style>
        .content, .container-fluid {
            overflow-y: auto !important;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('kiosk') }}"
                       class="btn-kiosk btn-lg btn-kiosk-blue">На главную</a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-default-success text-center mt-3">
                    <h3>{{ session('success') }}</h3>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-default-danger text-center mt-3">
                    <h3>{{ session('error') }}</h3>
                </div>
            @endif

            @if($roll)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Рулон: {{ $roll->roll_code }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Материал</th>
                                        <td>{{ $roll->material->title }}</td>
                                    </tr>
                                    <tr>
                                        <th>Начальный метраж</th>
                                        <td>{{ $roll->initial_quantity }} {{ $roll->material->unit }}</td>
                                    </tr>
                                    <tr>
                                        <th>Текущий метраж (по системе)</th>
                                        <td class="font-weight-bold"
                                            style="font-size: 1.2rem;">
                                            {{ $roll->current_quantity }} {{ $roll->material->unit }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Статус</th>
                                        <td><span
                                                class="badge {{ $roll->status_color }}"> {{ $roll->status_name }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                @if($roll->status === \App\Models\Roll::STATUS_IN_WORKSHOP)
                                    <form
                                        action="{{ route('kiosk.complete-roll') }}"
                                        method="POST">
                                        @csrf
                                        <input type="hidden" name="roll_id"
                                               value="{{ $roll->id }}">
                                        <div class="form-group">
                                            <label for="actualRemaining"
                                                   style="font-size: 1.2rem; font-weight: bold;">
                                                Фактический остаток в рулоне
                                            </label>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <input type="number"
                                                           step="0.01" min="0"
                                                           class="form-control form-control-lg"
                                                           name="actual_remaining"
                                                           id="actualRemaining"
                                                           value="{{ old('actual_remaining') }}"
                                                           required readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <button type="submit"
                                                            class="btn btn-danger"
                                                            style="font-size: 1.4rem;">
                                                        Завершить рулон
                                                    </button>
                                                </div>
                                            </div>

                                            <button type="button"
                                                    class="btn btn-success mt-1 mr-2"
                                                    data-step="0.01">+0.01
                                            </button>
                                            <button type="button"
                                                    class="btn btn-success mt-1 mr-2"
                                                    data-step="0.10">+0.10
                                            </button>
                                            <button type="button"
                                                    class="btn btn-success mt-1 mr-2"
                                                    data-step="1.00">+1.00
                                            </button>
                                            <br>
                                            <button type="button"
                                                    class="btn btn-danger mt-1 mr-2"
                                                    data-step="-0.01">- 0.01
                                            </button>
                                            <button type="button"
                                                    class="btn btn-danger mt-1 mr-2"
                                                    data-step="-0.10">- 0.10
                                            </button>
                                            <button type="button"
                                                    class="btn btn-danger mt-1 mr-2"
                                                    data-step="-1.00">- 1.00
                                            </button>
                                        </div>
                                    </form>
                                @elseif($roll->status === \App\Models\Roll::STATUS_COMPLETED)
                                    <div
                                        class="alert alert-secondary text-center mt-3">
                                        <h4>Рулон уже завершен</h4>
                                        @if($roll->completedBy)
                                            <h5>
                                                Закрыл(а): {{ $roll->completedBy->name }}</h5>
                                        @endif
                                    </div>
                                @elseif($roll->status === \App\Models\Roll::STATUS_SHIPPED_TO_WORKSHOP)
                                    <div
                                        class="alert alert-info text-center mt-3">
                                        <h4>Рулон отгружен в цех, ожидает
                                            приёмки</h4>
                                    </div>
                                @else
                                    <div
                                        class="alert alert-warning text-center mt-3">
                                        <h4>Рулон на складе — нельзя
                                            завершить</h4>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Расход материала</h3>
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
                                    <th scope="col">Тип</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($roll->movementMaterialsNotFromSuppler as $mm)
                                    @php
                                        $order = $mm->order;
                                        $type = $order?->type_movement;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $order?->created_at?->format('d/m/Y H:i') }}</td>
                                        <td>{{ $mm->quantity }}</td>
                                        <td>
                                            @if($type == 3 && $order?->marketplaceOrder)
                                                Заказ {{ $order->marketplaceOrder->order_id }}
                                            @else
                                                {{ \App\Models\TypeMovement::TYPES[$type] ?? '—' }}
                                            @endif
                                        </td>
                                        <td>{{ \App\Models\TypeMovement::TYPES[$type] ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="text-muted text-center">
                                            Нет записей о расходе
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                @if($lowMaterialRolls && $lowMaterialRolls->isNotEmpty())
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-exclamation-triangle"></i>
                                Рулонов с малым
                                остатком: {{ $lowMaterialRolls->count() }}
                            </h4>
                        </div>
                        <div class="card-body p-0">
                            <table
                                class="table table-hover table-bordered mb-0">
                                <thead class="thead-light">
                                <tr>
                                    <th>Код</th>
                                    <th>Материал</th>
                                    <th>Остаток</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($lowMaterialRolls as $lowRoll)
                                    <tr class="table-danger">
                                        <td class="font-weight-bold">{{ $lowRoll->roll_code }}</td>
                                        <td>{{ $lowRoll->material->title }}</td>
                                        <td>
                                            <span
                                                class="font-weight-bold text-danger">
                                                {{ $lowRoll->computed_quantity }}
                                            </span>
                                            / {{ $lowRoll->initial_quantity }}
                                            {{ $lowRoll->material->unit }}
                                        </td>
                                        <td>
                                            <a href="{{ route('kiosk.rolls', ['roll' => $lowRoll->roll_code]) }}"
                                               class="btn btn-sm btn-danger">
                                                Завершить
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="alert alert-default-info text-center mt-3">
                    <h3>Отсканируйте штрих-код рулона</h3>
                </div>

                @if(request()->filled('roll'))
                    <div class="alert alert-default-danger text-center mt-3">
                        <h3>Рулон не найден</h3>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<x-idle-modal-component/>

<script>
    const actionUrl = '{{ route('kiosk.rolls') }}';

    let buffer = '';
    let lastTime = Date.now();

    document.addEventListener('keypress', e => {
        const now = Date.now();

        if (now - lastTime > 200) {
            buffer = '';
        }
        lastTime = now;

        if (e.key === 'Enter') {
            if (buffer.trim().length > 0) {
                window.location.href = actionUrl + '?roll=' + encodeURIComponent(buffer);
            }
            buffer = '';
        } else {
            buffer += e.key;
        }
    });

    document.querySelectorAll('[data-step]').forEach(button => {
        button.addEventListener('click', function () {
            const step = parseFloat(this.getAttribute('data-step'));
            const quantityInput = document.getElementById('actualRemaining');
            let currentValue = parseFloat(quantityInput.value) || 0;
            let newValue = currentValue + step;

            if (newValue < 0) {
                newValue = 0;
            }

            quantityInput.value = newValue.toFixed(2);
            this.blur();
        });
    });
</script>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlite.js') }}"></script>
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

</body>
</html>
