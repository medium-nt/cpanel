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

    <style>
        html, body {
            height: 100%;
            overflow: hidden;
            margin: 0;
            padding: 0;
            touch-action: none;
        }

        .wrapper {
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .container-fluid {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .kiosk-buttons-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 1rem;
            padding: 1rem;
            flex: 1;
            min-height: 0;
        }

        .btn-kiosk {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: transform 0.1s, box-shadow 0.1s;
        }

        .btn-kiosk:active {
            transform: scale(0.98);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .btn-kiosk-blue {
            background-color: #3b82f6;
            color: white;
        }

        .btn-kiosk-green {
            background-color: #22c55e;
            color: white;
        }

        .btn-kiosk-yellow {
            background-color: #eab308;
            color: black;
        }

        .btn-kiosk-red {
            background-color: #ef4444;
            color: white;
        }

        .btn-kiosk-purple {
            background-color: #a855f7;
            color: white;
        }

        .btn-kiosk-orange {
            background-color: #f97316;
            color: white;
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

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Новый брак/остаток</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('defects') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="quantity">Количество</label>
                                    <input type="number" step="0.01"
                                           class="form-control" name="quantity"
                                           id="quantity" required
                                           value="{{ old('quantity') }}"
                                           readonly>

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
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="roll">Рулон</label>
                                    <input type="text" class="form-control"
                                           name="roll" id="roll"
                                           value="{{ old('roll') }}" autofocus
                                           required readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="material">Материал</label>
                                    <input type="text" class="form-control"
                                           id="material" readonly>
                                </div>
                            </div>
                        </div>
                        <button type="submit"
                                class="btn btn-kiosk btn-success mt-3">Создать
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal для предупреждения о неактивности -->
<x-idle-modal-component/>

<script>
    let roll = document.getElementById('roll');

    let buffer = '';
    let lastTime = Date.now();

    document.addEventListener('keypress', e => {
        const now = Date.now();

        // если пауза — считаем, что начался новый скан
        if (now - lastTime > 200) {
            buffer = '';
        }
        lastTime = now;

        if (e.key === 'Enter') {
            roll.value = buffer;
            buffer = '';
        } else {
            buffer += e.key;
        }
    });

    // Обработчики для кнопок изменения количества
    document.querySelectorAll('[data-step]').forEach(button => {
        button.addEventListener('click', function () {
            const step = parseFloat(this.getAttribute('data-step'));
            const quantityInput = document.getElementById('quantity');
            let currentValue = parseFloat(quantityInput.value) || 0;
            let newValue = currentValue + step;

            // Ограничение: не меньше 0
            if (newValue < 0) {
                newValue = 0;
            }

            // Форматирование до 2 знаков после запятой
            quantityInput.value = newValue.toFixed(2);

            // Убираем фокус с кнопки
            this.blur();
        });
    });
</script>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

</body>
</html>
